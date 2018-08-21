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
 *
 */
class Client implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The version of Luna.
     * @var string
     */
    const VERSION = '0.1.0-dev';
    
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
     * @param
     */
    function __construct() {
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
     * Adds a node.
     * @param \CharlotteDunois\Luna\Node $node
     * @return $this
     */
    function addNode(\CharlotteDunois\Luna\Node $node) {
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
     * @param \CharlotteDunois\Luna\Node $node
     * @return $this
     */
    function removeNode(\CharlotteDunois\Luna\Node $node) {
        $node->link->disconnect();
        
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
            return $node->connect();
        }));
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
