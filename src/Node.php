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
 * This class represents a node. Each node connects to the representing lavalink node.
 */
class Node implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The version of Luna.
     * @var string
     */
    const VERSION = '0.1.0-dev';
    
    /**
     * The player.
     * @var \CharlotteDunois\Luna\Player
     */
    protected $player;
    
    /**
     * The link to the lavalink node.
     * @var \CharlotteDunois\Luna\Node
     */
    protected $link;
    
    /**
     * The name for the node.
     * @var string
     */
    protected $name;
    
    /**
     * The password for the node.
     * @var string
     */
    protected $password;
    
    /**
     * The HTTP host address.
     * @var string
     */
    protected $httpHost;
    
    /**
     * The WS host address.
     * @var string
     */
    protected $wsHost;
    
    /**
     * The region the node (used for failover).
     * @var string
     */
    protected $region;
    
    /**
     * Constructor.
     * @param string  $name      The name for the node.
     * @param string  $password  The password.
     * @param string  $httpHost  The complete URI to the node's HTTP API.
     * @param string  $wsHost    The complete URI to the node's Websocket server.
     * @param string  $region    A region identifier. Used to decide which is the best node to switch to when a node fails.
     */
    function __construct(string $name, string $password, string $httpHost, string $wsHost, string $region) {
        
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
}
