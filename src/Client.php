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
     *     'connector' => \React\Socket\Connector, (a specific connector instance to use)
     * )
     * ```
     *
     * @param \React\EventLoop\LoopInterface  $loop
     * @param int                             $userID     The Discord User ID.
     * @param int                             $numShards  The amount of shards the bot has.
     * @param array                           $options    Optional options.
     */
    function __construct(\React\EventLoop\LoopInterface $loop, int $userID, int $numShards, array $options = array()) {
        $this->loop = $loop;
        $this->userID = $userID;
        $this->numShards = $numShards;
        
        $this->nodes = new \CharlotteDunois\Collect\Collection();
        $this->nodeListeners = new \CharlotteDunois\Collect\Collection();
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
            'disconnect' => $this->createDisconnectListener($node)
        );
        
        foreach($listeners as $event => $listener) {
            $node->link->on($event, $listener);
        }
        
        $this->nodes->set($node->name, $node);
        $this->nodeListeners->set($node->name, $listeners);
        
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
            $node->link->removeListener($event, $listener);
        }
        
        $this->nodes->delete($node->name);
        $this->nodeListeners->delete($node->name);
        
        return $this;
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
     * @param bool                          $autoConnect  Whether we automatically open an connection to the node.
     * @return \CharlotteDunois\Luna\Node[]
     */
    function createNodes(array $nodes, bool $autoConnect = true) {
        $factory = array();
        
        foreach($nodes as $node) {
            list('name' => $name, 'password' => $password, 'httpHost' => $httpHost, 'wsHost' => $wsHost, 'region' => $region) = $node;
            $node = new \CharlotteDunois\Luna\Node($this, $name, $password, $httpHost, $wsHost, $region);
            
            $factory[$name] = $node;
            $this->addNode($node, $autoConnect);
        }
        
        return $factory;
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
        return (function (int $code, string $reason) use (&$node) {
            $this->emit('disconnect', $node, $code, $reason);
        });
    }
}
