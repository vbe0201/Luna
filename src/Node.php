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
 * @property \CharlotteDunois\Luna\Client|null       $client           The Luna client.
 * @property \CharlotteDunois\Collect\Collection     $players          All players of the node, mapped by guild ID.
 * @property \CharlotteDunois\Luna\Link|null         $link             The websocket connection to the lavalink node.
 * @property \CharlotteDunois\Luna\RemoteStats|null  $stats            The lavalink node's stats, or null.
 * @property string                                  $name             The name of the node.
 * @property string                                  $password         The password of th enode.
 * @property string                                  $httpHost         The HTTP host address.
 * @property string                                  $wsHost           The WS host address.
 * @property string                                  $region           The region of the node.
 * @property array|null                              $lastVoiceUpdate  The last sent voice update event.
 */
class Node implements \CharlotteDunois\Events\EventEmitterInterface, \JsonSerializable {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The Luna client.
     * @var \CharlotteDunois\Luna\Client|null
     */
    protected $client;
    
    /**
     * All players of the node, mapped by guild ID.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $players;
    
    /**
     * The link to the lavalink node.
     * @var \CharlotteDunois\Luna\Link|null
     */
    protected $link;
    
    /**
     * Lavalink stats.
     * @var \CharlotteDunois\Luna\RemoteStats|null
     */
    protected $stats;
    
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
     * The last sent voice update event.
     * @var array|null
     */
    protected $lastVoiceUpdate;
    
    /**
     * Constructor.
     * @param string  $name      The name for the node.
     * @param string  $password  The password.
     * @param string  $httpHost  The complete URI to the node's HTTP API.
     * @param string  $wsHost    The complete URI to the node's Websocket server.
     * @param string  $region    A region identifier. Used to decide which is the best node to switch to when a node fails.
     */
    function __construct(string $name, string $password, string $httpHost, string $wsHost, string $region) {
        $this->players = new \CharlotteDunois\Collect\Collection();
        
        $this->name = $name;
        $this->password = $password;
        $this->httpHost = $httpHost;
        $this->wsHost = $wsHost;
        $this->region = $region;
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
     * Sets the client and creates a Link. Invoked in `Client::addNode`.
     * @param \CharlotteDunois\Luna\Client  $client
     * @return void
     */
    function setClient(\CharlotteDunois\Luna\Client $client) {
        $this->client = $client;
        $this->link = new \CharlotteDunois\Luna\Link($client, $this);
    }
    
    /**
     * Send a voice update event to the node, creates a new player and adds it to the collection.
     * @param int     $guildID
     * @param string  $sessionID
     * @param array   $event
     * @return \CharlotteDunois\Luna\Player
     * @throws \BadMethodCallException
     */
    function sendVoiceUpdate(int $guildID, string $sessionID, array $event) {
        $this->_sendVoiceUpdate($guildID, $sessionID, $event);
        
        $player = new \CharlotteDunois\Luna\Player($this, $guildID);
        $this->players->set($guildID, $player);
        
        return $player;
    }
    
    /**
     * Resolves a track using Lavalink's REST API. Resolves with an instance of AudioTrack, an instance of AudioPlaylist or a Collection of AudioTrack instances (for search results), mapped by the track identifier.
     * @param string  $search  The search query.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException
     * @throws \RangeException              The exception the promise gets rejected with, when there are no matches.
     * @throws \UnexpectedValueException    The exception the promise gets rejected with, when loading the track failed.
     * @see \CharlotteDunois\Luna\AudioTrack
     * @see \CharlotteDunois\Luna\AudioPlaylist
     */
    function resolveTrack(string $search) {
        if(!$this->client) {
            throw new \BadMethodCallException('Node has no client');
        }
        
        $this->emit('debug', 'Resolving track "'.$search.'"');
        
        return $this->client->createHTTPRequest('GET', $this->httpHost.'/loadtracks?identifier='.\rawurlencode($search), array(
            'Authorization' => $this->password
        ))->then(function (\Psr\Http\Message\ResponseInterface $response) {
            $body = (string) $response->getBody();
            $data = \json_decode($body, true);
            
            if($data === false && \json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON while trying to resolve tracks. Error: '.\json_last_error_msg());
            }
            
            switch(($data['loadType'] ?? null)) {
                case 'TRACK_LOADED':
                    return \CharlotteDunois\Luna\AudioTrack::create($data['tracks'][0]);
                break;
                case 'PLAYLIST_LOADED':
                    return (new \CharlotteDunois\Luna\AudioPlaylist(($data['playlistInfo']['name'] ?? ''), ($data['playlistInfo']['selectedTrack'] ?? 0), $data['tracks']));
                break;
                case 'SEARCH_RESULT':
                    $bucket = new \CharlotteDunois\Collect\Collection();
                    
                    foreach($data['tracks'] as $track) {
                        $audioTrack = \CharlotteDunois\Luna\AudioTrack::create($track);
                        $bucket->set($audioTrack->identifier, $audioTrack);
                    }
                    
                    return $bucket;
                break;
                case 'NO_MATCHES':
                    throw new \RangeException('No matching tracks found');
                break;
                case 'LOAD_FAILED':
                    throw new \UnexpectedValueException('Loading track failed');
                break;
            }
        });
    }
    
    /**
     * Updates stats of this node.
     * @param array  $stats
     * @return \CharlotteDunois\Luna\RemoteStats
     * @internal
     */
    function _updateStats(array $stats) {
        if($this->stats) {
            $this->stats->update($stats);
        } else {
            $this->stats = new \CharlotteDunois\Luna\RemoteStats($this, $stats);
        }
        
        return $this->stats;
    }
    
    /**
     * Send a voice update event to the node.
     * @param int     $guildID
     * @param string  $sessionID
     * @param array   $event
     * @return void
     * @throws \BadMethodCallException
     * @internal
     */
    function _sendVoiceUpdate(int $guildID, string $sessionID, array $event) {
        if(!$this->client) {
            throw new \BadMethodCallException('Node has no client');
        }
        
        $packet = array(
            'op' => 'voiceUpdate',
            'guildId' => ((string) $guildID),
            'sessionId' => $sessionID,
            'event' => $event
        );
        
        $this->emit('debug', 'Sending voice update for guild '.$guildID);
        
        $this->link->send($packet);
        $this->lastVoiceUpdate = $packet;
    }
}
