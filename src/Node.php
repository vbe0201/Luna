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
class Node implements \JsonSerializable {
    /**
     * The client.
     * @var \CharlotteDunois\Luna\Client
     */
    protected $client;

    /**
     * All players of the node.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $players;
    
    /**
     * The link to the lavalink node.
     * @var \CharlotteDunois\Luna\Link
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
     * @param \CharlotteDunois\Luna\Client  $client
     * @param string                        $name      The name for the node.
     * @param string                        $password  The password.
     * @param string                        $httpHost  The complete URI to the node's HTTP API.
     * @param string                        $wsHost    The complete URI to the node's Websocket server.
     * @param string                        $region    A region identifier. Used to decide which is the best node to switch to when a node fails.
     */
    function __construct(\CharlotteDunois\Luna\Client $client, string $name, string $password, string $httpHost, string $wsHost, string $region) {
        $this->client = $client;
        $this->link = new \CharlotteDunois\Luna\Link($client, $this);
        $this->players = new \CharlotteDunois\Collect\Collection();
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
    
    /**
     * Creates a new player and adds it to the collection.
     * @param int  $guildID
     * @return \CharlotteDunois\Luna\Player
     */
    function createPlayer(int $guildID) {
        $player = new \CharlotteDunois\Luna\Player($this, $guildID);
        $this->players->set($guildID, $player);
        
        return $player;
    }
    
    /**
     *
     */
    function resolveTrack() {
        
    }
}
