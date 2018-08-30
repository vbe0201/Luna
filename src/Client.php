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
 * The generic Lavalink client. It does absolutely nothing for you on the Discord side.
 * The lavalink client implements automatic failover. That means, if a lavalink node unexpectedly disconnects,
 * the client will automatically look for a new node and starts playing the track on it.
 * @property \CharlotteDunois\Luna\LoadBalancer|null  $loadBalancer The Load Balancer.
 * @property \CharlotteDunois\Collect\Collection      $links        A collection of links, mapped by node name.
 * @property int                                      $numShards    The amount of shards the bot has.
 * @property int                                      $userID       The Discord User ID.
 * @see \CharlotteDunois\Luna\ClientEvents
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
     * The Load Balancer.
     * @var \CharlotteDunois\Luna\LoadBalancer|null
     */
    protected $loadBalancer;
    
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
     * A collection of links, mapped by name.
     * @param \CharlotteDunois\Collect\Collection
     */
    protected $links;
    
    /**
     * A collection of link listeners, mapped by name.
     * @param \CharlotteDunois\Collect\Collection
     */
    protected $linkListeners;
    
    /**
     * Constructor.
     *
     * Optional options are as following:
     * ```
     * array(
     *     'connector' => \React\Socket\Connector, (a specific connector instance to use for both the websocket and the HTTP client)
     *     'node.maxConnectAttempts' => int, (maximum attempts at (re)connecting to a node, defaults to 0 (unlimited))
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
        
        $this->links = new \CharlotteDunois\Collect\Collection();
        $this->linkListeners = new \CharlotteDunois\Collect\Collection();
        
        $failover = function (\CharlotteDunois\Luna\Link $link, int $code, string $reason, bool $expectedClose, ?\CharlotteDunois\Collect\Collection $nplayers = null) use (&$failover) {
            $players = ($nplayers ?: $link->players);
            
            if(!$expectedClose && $players->count() > 0) {
                $link->emit('debug', 'Failing over '.$players->count().' player(s) to other nodes');
                
                try {
                    foreach($players as $player) {
                        $track = $player->track;
                        $position = $player->getLastPosition();
                        
                        if($this->loadBalancer) {
                            $newNode = $this->loadBalancer->getIdealNode($link->node->region);
                        } else {
                            $newNode = $this->getIdealNode($link->node->region);
                        }
                        
                        $player->setNode($newNode);
                        $player->sendVoiceUpdate($player->voiceServerUpdate['sessionID'], $player->voiceServerUpdate['event']);
                        
                        if($track) {
                            $player->play($track, $position);
                        }
                        
                        $this->emit('failover', $link, $player);
                    }
                } catch (\UnderflowException $e) {
                    $link->emit('debug', 'Delaying failover by 10 seconds due to no links available');
                    
                    if($nplayers === null) {
                        $nplayers = new \CharlotteDunois\Collect\Collection();
                        
                        foreach($players as $key => $player) {
                            $nplayers->set($key, (clone $player));
                        }
                    }
                    
                    $this->loop->addTimer(10, function () use ($link, $code, $reason, $expectedClose, $nplayers, &$failover) {
                        $failover($link, $code, $reason, $expectedClose, $nplayers);
                    });
                }
            }
        };
        
        $this->on('disconnect', $failover);
    }
    
    /**
     * @param string  $name
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
     * @param string  $name
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
     * Sets a loadbalancer to use.
     * @param \CharlotteDunois\Luna\LoadBalancer  $loadBalancer
     * @return void
     */
    function setLoadBalancer(\CharlotteDunois\Luna\LoadBalancer $loadBalancer) {
        $this->loadBalancer = $loadBalancer;
        $this->loadBalancer->setClient($this);
    }
    
    /**
     * Adds a node.
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @return $this
     */
    function addNode(\CharlotteDunois\Luna\Node $node) {
        if($this->links->has($node->name)) {
            return $this;
        }
        
        $link = new \CharlotteDunois\Luna\Link($this, $node);
        
        $listeners = array(
            'debug' => $this->createDebugListener($link),
            'error' => $this->createErrorListener($link),
            'disconnect' => $this->createDisconnectListener($link),
            'stats' => $this->createStatsListener($link),
            'newPlayer' => $this->createNewPlayerListener($link)
        );
        
        foreach($listeners as $event => $listener) {
            $link->on($event, $listener);
        }
        
        $this->links->set($node->name, $link);
        $this->linkListeners->set($node->name, $listeners);
        
        return $this;
    }
    
    /**
     * Removes a node.
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @param bool                        $autoDisconnect  Whether we automatically disconnect from the node.
     * @return $this
     */
    function removeNode(\CharlotteDunois\Luna\Node $node, bool $autoDisconnect = true) {
        if(!$this->links->has($node->name)) {
            return $this;
        }
        
        $link = $this->links->has($node->name);
        $listeners = $this->linkListeners->get($node->name);
        
        if($autoDisconnect) {
            $link->disconnect();
        }
        
        foreach($listeners as $event => $listener) {
            $link->removeListener($event, $listener);
        }
        
        $this->links->delete($node->name);
        $this->linkListeners->delete($node->name);
        
        return $this;
    }
    
    /**
     * Get an ideal node for the region solely based on region. If there is no ideal node, this will return the first connected node in the list.
     * @param string  $region
     * @param bool    $autoConnect  Automatically make the node connect if it is disconnected (idling).
     * @return \CharlotteDunois\Luna\Link
     * @throws \UnderflowException  Thrown when no nodes are available
     */
    function getIdealNode(string $region, bool $autoConnect = true) {
        if($this->links->count() === 0) {
            throw new \UnderflowException('No nodes added');
        }
        
        $link = $this->links->first(function (\CharlotteDunois\Luna\Link $link) use ($region) {
            return ($link->node->region === $region && $link->status >= \CharlotteDunois\Luna\Link::STATUS_CONNECTED);
        });
        
        if(!$link) {
            $link = $this->links->first(function (\CharlotteDunois\Luna\Link $link) {
                return ($link->status >= \CharlotteDunois\Luna\Link::STATUS_CONNECTING);
            });
            
            if(!$link) {
                throw new \UnderflowException('No node available');
            }
        }
        
        if($link->status === \CharlotteDunois\Luna\Link::STATUS_IDLE) {
            $link->connect();
        }
        
        return $link;
    }
    
    /**
     * Starts all connections to the links.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function start() {
        return \React\Promise\all($this->links->map(function (\CharlotteDunois\Luna\Link $link) {
            return $link->connect();
        })->all());
    }
    
    /**
     * Stops all connections to the links.
     * @return void
     */
    function stop() {
        foreach($this->links as $link) {
            $link->disconnect();
        }
    }
    
    /**
     * Creates nodes as part of a factory and adds them to the client. This is useful to import node configurations from a file.
     * @param array                         $links
     * @return \CharlotteDunois\Luna\Node[]
     */
    function createNodes(array $nodes) {
        $factory = array();
        
        foreach($nodes as $data) {
            list('name' => $name, 'password' => $password, 'httpHost' => $httpHost, 'wsHost' => $wsHost, 'region' => $region) = $data;
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
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @return \Closure
     */
    protected function createDebugListener(\CharlotteDunois\Luna\Link $link) {
        return (function ($debug) use (&$link) {
            $this->emit('debug', $link, $debug);
        });
    }
    
    /**
     * Creates a node-specific error listener.
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @return \Closure
     */
    protected function createErrorListener(\CharlotteDunois\Luna\Link $link) {
        return (function (\Throwable $error) use (&$link) {
            $this->emit('error', $link, $error);
        });
    }
    
    /**
     * Creates a node-specific disconnect listener.
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @return \Closure
     */
    protected function createDisconnectListener(\CharlotteDunois\Luna\Link $link) {
        return (function (int $code, string $reason, bool $expectedClose) use (&$link) {
            $this->emit('disconnect', $link, $code, $reason, $expectedClose);
        });
    }
    
    /**
     * Creates a node-specific stats listener.
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @return \Closure
     */
    protected function createStatsListener(\CharlotteDunois\Luna\Link $link) {
        return (function (\CharlotteDunois\Luna\RemoteStats $stats) use (&$link) {
            $this->emit('stats', $link, $stats);
        });
    }
    
    /**
     * Creates a node-specific newPlayer listener.
     * @param $link\CharlotteDunois\Luna\Link  $link
     * @return \Closure
     */
    protected function createNewPlayerListener(\CharlotteDunois\Luna\Link $link) {
        return (function (\CharlotteDunois\Luna\Player $player) use (&$link) {
            $this->emit('newPlayer', $link, $player);
        });
    }
}
