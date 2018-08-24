<?php
/**
 * Luna
 * Copyright 2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Luna/blob/master/LICENSE
*/

namespace CharlotteDunois\Luna;

/**
 * The generic Lavalink Client. It does absolutely nothing for you on the Discord side.
 * The lavalink Client implements automatic failover. That means, if a lavalink node unexpectedly disconnects,
 * the client will automatically look for a new node and starts playing the track on it.
 * @property \CharlotteDunois\Collect\Collection  $nodes      A collection of nodes, mapped by name.
 * @property int                                  $numShards  The amount of shards the bot has.
 * @property int                                  $userID     The Discord User ID.
 */
class Client implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The version of Luna.
     * @var string
     */
    const VERSION = '0.1.0-dev';
    
    /**
     * The event loop.
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    
    /**
     * The HTTP client.
     * @var \Clue\React\Buzz\Browser|null
     */
    protected $browser;
    
    /**
     * The Discord User ID.
     * @var int
     */
    protected $userID;
    
    /**
     * The amount of shards the bot has.
     * @var int
     */
    protected $numShards;
    
    /**
     * Optional options.
     * @var array
     * @internal
     */
    protected $options = array();
    
    /**
     * A collection of nodes, mapped by name.
     * @param \CharlotteDunois\Collect\Collection
     */
    protected $nodes;
    
    /**
     * A collection of node listeners, mapped by name.
     * @param \CharlotteDunois\Collect\Collection
     */
    protected $nodeListeners;
    
    /**
     * Constructor.
     *
     * Optional options are as following:
     * ```
     * array(
     *     'connector' => \React\Socket\Connector, (a specific connector instance to use for both the websocket and the HTTP client)
     *     'loadbalancer' => \CharlotteDunois\Luna\LoadBalancer, (a loadbalancer to use)
     * )
     * ```
     *
     * @param \React\EventLoop\LoopInterface  $loop
     * @param int                             $userID     The Discord User ID.
     * @param int                             $numShards  The amount of shards the bot has.
     * @param array                           $options    Optional options.
     */
    function __construct(\React\EventLoop\LoopInterface $loop, int $userID, int $numShards = 1, array $options = array()) {
        $this->loop = $loop;
        $this->userID = $userID;
        $this->numShards = $numShards;
        $this->options = \array_merge($this->options, $options);
        
        if($this->getOption('internal.disableBrowser', false) !== true) {
            $this->browser = new \Clue\React\Buzz\Browser($loop, $this->getOption('connector'));
        }
        
        $this->nodes = new \CharlotteDunois\Collect\Collection();
        $this->nodeListeners = new \CharlotteDunois\Collect\Collection();
        
        $this->on('disconnect', function (\CharlotteDunois\Luna\Node $node, int $code, string $reason, bool $expectedClose) {
            if(!$expectedClose && $node->players->count() > 0) {
                $node->emit('debug', 'Failing over '.$node->players->count().' player(s) to new nodes');
                
                $loadbalancer = $this->getOption('loadbalancer');
                
                foreach($node->players as $player) {
                    $track = $player->track;
                    $position = $player->getLastPosition();
                    
                    $player->destroy();
                    
                    if($loadbalancer instanceof \CharlotteDunois\Luna\LoadBalancer) {
                        $newNode = $loadbalancer->getIdealNode($node->region);
                    } else {
                        $newNode = $this->getIdealNode($node->region);
                    }
                    
                    $newPlayer = $newNode->sendVoiceUpdate($node->lastVoiceUpdate['guildId'], $node->lastVoiceUpdate['sessionId'], $node->lastVoiceUpdate['event']);
                    
                    if($track) {
                        $newPlayer->play($track, $position);
                    }
                    
                    $this->emit('failover', $node, $newPlayer);
                }
            } else {
                foreach($node->players as $player) {
                    $player->destroy();
                }
            }
        });
    }
    
    /**
     * @return bool
     * @throws \RuntimeException
     * @internal
     */
    function __isset($name) {
        try {
            return ($this->$name !== null);
        } catch (\RuntimeException $e) {
            if($e->getTrace()[0]['function'] === '__get') {
                return false;
            }
            
            throw $e;
        }
    }
    
    /**
     * @return mixed
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        throw new \RuntimeException('Undefined property: '.\get_class($this).'::$'.$name);
    }
    
    /**
     * Returns the event loop.
     * @return \React\EventLoop\LoopInterface
     */
    function getLoop() {
        return $this->loop;
    }
    
    /**
     * Get a specific option, or the default value.
     * @param string  $name
     * @param mixed   $default
     * @return mixed
     */
    function getOption($name, $default = null) {
        if(isset($this->options[$name])) {
            return $this->options[$name];
        }
        
        return $default;
    }
    
    /**
     * Adds a node.
     * @param \CharlotteDunois\Luna\Node  $node
     * @return $this
     */
    function addNode(\CharlotteDunois\Luna\Node $node) {
        if($this->nodes->has($node->name)) {
            return $this;
        }
        
        $listeners = array(
            'debug' => $this->createDebugListener($node),
            'error' => $this->createErrorListener($node),
            'disconnect' => $this->createDisconnectListener($node),
            'stats' => $this->createStatsListener($node)
        );
        
        foreach($listeners as $event => $listener) {
            $node->on($event, $listener);
        }
        
        $this->nodes->set($node->name, $node);
        $this->nodeListeners->set($node->name, $listeners);
        
        $node->setClient($this);
        return $this;
    }
    
    /**
     * Removes a node.
     * @param \CharlotteDunois\Luna\Node  $node
     * @param bool                        $autoDisconnect  Whether we automatically disconnect from the node.
     * @return $this
     */
    function removeNode(\CharlotteDunois\Luna\Node $node, bool $autoDisconnect = true) {
        if(!$this->nodes->has($node->name)) {
            return $this;
        }
        
        if($autoDisconnect) {
            $node->link->disconnect();
        }
        
        $listeners = $this->nodeListeners->get($node->name);
        
        foreach($listeners as $event => $listener) {
            $node->removeListener($event, $listener);
        }
        
        $this->nodes->delete($node->name);
        $this->nodeListeners->delete($node->name);
        
        return $this;
    }
    
    /**
     * Get an ideal node for the region. If there is no ideal node, this will return the first node in the list.
     * @param string  $region
     * @param bool    $autoConnect  Automatically make the node connect if it is disconnected (idling).
     * @return \CharlotteDunois\Luna\Node
     * @throws \UnderflowException  Thrown when no nodes are available
     */
    function getIdealNode(string $region, bool $autoConnect = true) {
        if($this->nodes->count() === 0) {
            throw new \UnderflowException('No nodes added');
        }
        
        $node = $this->nodes->first(function (\CharlotteDunois\Luna\Node $node) use ($region) {
            return ($node->region === $region && $node->link->status >= \CharlotteDunois\Luna\Link::STATUS_CONNECTED);
        });
        
        if(!$node) {
            $node = $this->nodes->first(function (\CharlotteDunois\Luna\Node $node) {
                return ($node->link->status >= \CharlotteDunois\Luna\Link::STATUS_CONNECTED);
            });
            
            if(!$node) {
                throw new \UnderflowException('No node available');
            }
        }
        
        if($node->link->status === \CharlotteDunois\Luna\Link::STATUS_IDLE) {
            $node->link->connect();
        }
        
        return $node;
    }
    
    /**
     * Starts all connections to the nodes.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function start() {
        return \React\Promise\all($this->nodes->map(function (\CharlotteDunois\Luna\Node $node) {
            return $node->link->connect();
        })->all());
    }
    
    /**
     * Stops all connections to the nodes.
     * @return void
     */
    function stop() {
        foreach($this->nodes as $node) {
            $node->link->disconnect();
        }
    }
    
    /**
     * Creates nodes as part of a factory. This is useful to import node configurations from a file.
     * @param array                         $nodes
     * @return \CharlotteDunois\Luna\Node[]
     */
    function createNodes(array $nodes) {
        $factory = array();
        
        foreach($nodes as $node) {
            list('name' => $name, 'password' => $password, 'httpHost' => $httpHost, 'wsHost' => $wsHost, 'region' => $region) = $node;
            $node = new \CharlotteDunois\Luna\Node($name, $password, $httpHost, $wsHost, $region);
            
            $factory[$name] = $node;
            $this->addNode($node);
        }
        
        return $factory;
    }
    
    /**
     * Executes an asychronous HTTP request. Resolves with an instance of ResponseInterface.
     * @param string       $method
     * @param string       $url
     * @param string[]     $headers
     * @param string|null  $body
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException
     * @internal
     */
    function createHTTPRequest(string $method, string $url, array $headers, string $body = null) {
        if(!$this->browser) {
            throw new \BadMethodCallException('Invoked createHTTPRequest method, but the browser is not available');
        }
        
        $method = \strtolower($method);
        return $this->browser->$method($url, $headers, $body);
    }
    
    /**
     * Creates a node-specific debug listener.
     * @param \CharlotteDunois\Luna\Node  $node
     * @return \Closure
     */
    protected function createDebugListener(\CharlotteDunois\Luna\Node $node) {
        return (function ($debug) use (&$node) {
            $this->emit('debug', $node, $debug);
        });
    }
    
    /**
     * Creates a node-specific error listener.
     * @param \CharlotteDunois\Luna\Node  $node
     * @return \Closure
     */
    protected function createErrorListener(\CharlotteDunois\Luna\Node $node) {
        return (function (\Throwable $error) use (&$node) {
            $this->emit('error', $node, $error);
        });
    }
    
    /**
     * Creates a node-specific disconnect listener.
     * @param \CharlotteDunois\Luna\Node  $node
     * @return \Closure
     */
    protected function createDisconnectListener(\CharlotteDunois\Luna\Node $node) {
        return (function (int $code, string $reason, bool $expectedClose) use (&$node) {
            $this->emit('disconnect', $node, $code, $reason, $expectedClose);
        });
    }
    
    /**
     * Creates a node-specific stats listener.
     * @param \CharlotteDunois\Luna\Node  $node
     * @return \Closure
     */
    protected function createStatsListener(\CharlotteDunois\Luna\Node $node) {
        return (function ($stats) use (&$node) {
            $this->emit('stats', $node, $stats);
        });
    }
}
