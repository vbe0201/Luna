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
 * This class represents a node.
 * @property string  $name      The name of the node.
 * @property string  $password  The password of th enode.
 * @property string  $httpHost  The HTTP host address.
 * @property string  $wsHost    The WS host address.
 * @property string  $region    The region of the node.
 */
class Node implements \JsonSerializable {
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
        $this->name = $name;
        $this->password = $password;
        $this->httpHost = $httpHost;
        $this->wsHost = $wsHost;
        $this->region = $region;
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
     * @return array
     * @internal
     */
    function jsonSerialize() {
        return array(
            'name' => $this->name,
            'password' => $this->password,
            'httpHost' => $this->httpHost,
            'wsHost' => $this->wsHost,
            'region' => $this->region
        );
    }
}
