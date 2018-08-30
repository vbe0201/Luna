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
 * A link connects to the lavalink node and listens for events and sends packets.
 * @property \CharlotteDunois\Luna\Client            $client   The Luna client.
 * @property \CharlotteDunois\Luna\Node              $node     The node this link is for.
 * @property \CharlotteDunois\Collect\Collection     $players  All players of the node, mapped by guild ID.
 * @property \CharlotteDunois\Luna\RemoteStats|null  $stats    The lavalink node's stats, or null.
 * @property int                                     $status   The connection status.
 * @see \CharlotteDunois\Luna\ClientEvents
 */
class Link implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * WS connection status: Disconnected.
     * @var int
     */
    const STATUS_DISCONNECTED = 0;
    
    /**
     * WS connection status: Connecting.
     * @var int
     */
    const STATUS_CONNECTING = 1;
    
    /**
     * WS connection status: Reconnecting.
     * @var int
     */
    const STATUS_RECONNECTING = 2;
    
    /**
     * WS connection status: Connected.
     * @var int
     */
    const STATUS_CONNECTED = 3;
    
    /**
     * WS connection status: Idling (disconnected and no reconnect planned).
     * @var int
     */
    const STATUS_IDLE = 4;
    
    /**
     * The client.
     * @var \CharlotteDunois\Luna\Client
     */
    protected $client;
    
    /**
     * The node this link is for.
     * @var \CharlotteDunois\Luna\Node
     */
    protected $node;
    
    /**
     * The lavalink version on the node.
     * @var int
     */
    protected $nodeVersion;
    
    /**
     * All players of the node, mapped by guild ID.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $players;
    
    /**
     * Lavalink stats.
     * @var \CharlotteDunois\Luna\RemoteStats|null
     */
    protected $stats;
    
    /**
     * @var \Ratchet\Client\Connector
     */
    protected $connector;
    
    /**
     * @var \Ratchet\Client\WebSocket
     */
    protected $ws;
    
    /**
     * Attempts at connecting.
     * @var int
     */
    protected $connectAttempts = 0;
    
    /**
     * The timer to acknowledge a connect as successful.
     * @var \React\EventLoop\TimerInterface|\React\EventLoop\Timer\TimerInterface|null
     */
    protected $connectTimer;
    
    /**
     * The promise of the connector.
     * @var \React\Promise\ExtendedPromiseInterface|null
     */
    protected $connectPromise;
    
    /**
     * Whether we expected the ws to close.
     * @var bool
     */
    protected $expectedClose = false;
    
    /**
     * The connection status.
     * @var int
     */
    protected $status = self::STATUS_DISCONNECTED;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Luna\Client  $client  Needed to get User ID, Num Shards and the event loop.
     * @param \CharlotteDunois\Luna\Node    $node    The node this link is for.
     */
    function __construct(\CharlotteDunois\Luna\Client $client, \CharlotteDunois\Luna\Node $node) {
        $this->client = $client;
        $this->node = $node;
        
        $this->players = new \CharlotteDunois\Collect\Collection();
        $this->connector = new \Ratchet\Client\Connector($client->getLoop(), $client->getOption('connector'));
    }
    
    /**
     * @param string  $name
     * @return bool
     * @throws \Exception
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
     * Connects to the node websocket.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \Exception
     */
    function connect() {
        $this->expectedClose = false;
        
        if($this->ws) {
            return \React\Promise\resolve();
        } elseif($this->connectPromise !== null) {
            return $this->connectPromise;
        }
        
        if($this->status < self::STATUS_CONNECTING || $this->status > self::STATUS_RECONNECTING) {
            $this->status = self::STATUS_CONNECTING;
        }
        
        $this->emit('debug', 'Connecting to node');
        $this->connectAttempts++;
        
        $connector = $this->connector;
        $this->connectPromise = $connector($this->node->wsHost, array(), array(
            'Authorization' => $this->node->password,
            'Num-Shards' => $this->client->numShards,
            'User-Id' => $this->client->userID
        ))->then(function (\Ratchet\Client\WebSocket $conn) {
            $this->setupWebsocket($conn);
        }, function (\Throwable $error) {
            $this->emit('error', $error);
            
            if($this->ws) {
                $this->ws->close(1006);
            }
            
            $maxAttempts = $this->client->getOption('node.maxConnectAttempts', 0);
            if($maxAttempts > 0 && $maxAttempts <= $this->connectAttempts) {
                $this->status = self::STATUS_IDLE;
                $this->emit('debug', 'Reached maximum connect attempts');
                
                throw new \RuntimeException('Reached maximum connect attempts');
            }
            
            $this->status = self::STATUS_DISCONNECTED;
            return $this->renewConnection(false);
        });
        
        return $this->connectPromise;
    }
    
    /**
     * Closes the connection to the node websocket.
     * @param int     $code
     * @param string  $reason
     * @return void
     */
    function disconnect(int $code = 1000, string $reason = '') {
        if(!$this->ws) {
            return;
        }
        
        $this->expectedClose = true;
        $this->ws->close($code, $reason);
    }
    
    /**
     * Renews the WS connection.
     * @param bool  $try
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function renewConnection(bool $try = true) {
        if($try) {
            return $this->connect()->otherwise(function () {
                $this->emit('debug', 'Error reconnecting after failed connection attempt... retrying in 30 seconds');
                return $this->scheduleConnect();
            });
        }
        
        $this->emit('debug', 'Scheduling reconnection for executing in 30 seconds');
        return $this->scheduleConnect();
    }
    
    /**
     * Schedules a connect.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function scheduleConnect() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->getLoop()->addTimer(30, function () use ($resolve, $reject) {
                $this->renewConnection()->done($resolve, $reject);
            });
        }));
    }
    
    /**
     * Sends a packet.
     * @param array $packet
     * @return void
     * @throws \RuntimeException
     */
    function send(array $packet) {
        if($this->status !== self::STATUS_CONNECTED) {
            if($this->connectPromise !== null) {
                $this->connectPromise->done(function () use ($packet) {
                    $this->send($packet);
                });
                
                return;
            }
            
            throw new \RuntimeException('Unable to send WS message before a WS connection is established');
        }
        
        $this->emit('debug', 'Sending WS packet');
        $this->ws->send(\json_encode($packet));
    }
    
    /**
     * Send a voice update event to the node, creates a new player and adds it to the collection.
     * @param int     $guildID    The guild ID.
     * @param string  $sessionID  The voice session ID.
     * @param array   $event      The voice server update event from Discord.
     * @return \CharlotteDunois\Luna\Player
     * @throws \BadMethodCallException
     */
    function createPlayer(int $guildID, string $sessionID, array $event) {
        $player = new \CharlotteDunois\Luna\Player($this, $guildID);
        $player->sendVoiceUpdate($sessionID, $event);
        
        $this->players->set($guildID, $player);
        $this->emit('newPlayer', $player);
        
        return $player;
    }
    
    /**
     * Resolves a track using Lavalink's REST API. Resolves with an instance of AudioTrack, an instance of AudioPlaylist or a Collection of AudioTrack instances (for search results), mapped by the track identifier.
     * @param string  $search  The search query.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \RangeException              The exception the promise gets rejected with, when there are no matches.
     * @throws \UnexpectedValueException    The exception the promise gets rejected with, when loading the track failed.
     * @see \CharlotteDunois\Luna\AudioTrack
     * @see \CharlotteDunois\Luna\AudioPlaylist
     */
    function resolveTrack(string $search) {
        $this->emit('debug', 'Resolving track "'.$search.'"');
        
        return $this->client->createHTTPRequest('GET', $this->node->httpHost.'/loadtracks?identifier='.\rawurlencode($search), array(
            'Authorization' => $this->node->password
        ))->then(function (\Psr\Http\Message\ResponseInterface $response) {
            $body = (string) $response->getBody();
            $data = \json_decode($body, true);
            
            if($data === false && \json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON while trying to resolve tracks. Error: '.\json_last_error_msg());
            }
            
            switch(($data['loadType'] ?? 'lavalink-v2')) {
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
                case 'lavalink-v2':
                    if(empty($data)) {
                        throw new \RangeException('No matching tracks found');
                    }
                    
                    if(\count($data) > 1) {
                        return \CharlotteDunois\Luna\AudioTrack::create($data[0]);
                    } else {
                        return (new \CharlotteDunois\Luna\AudioPlaylist(null, null, $data));
                    }
                break;
            }
        });
    }
    
    /**
     * Sets up the websocket. Resolver for the connector.
     * @param \Ratchet\Client\WebSocket  $conn
     * @return void
     * @throws \UnexpectedValueException
     */
    protected function setupWebsocket(\Ratchet\Client\WebSocket $conn) {
        if($conn->response->hasHeader('Lavalink-Major-Version')) {
            $this->nodeVersion = (int) $conn->response->getHeader('Lavalink-Major-Version')[0];
        }
        
        $this->ws = $conn;
        $this->status = self::STATUS_CONNECTED;
        
        $this->connectTimer = $this->client->getLoop()->addTimer(10, function () {
            if($this->status === self::STATUS_CONNECTED) {
                $this->connectAttempts = 0;
            }
            
            $this->connectTimer = null;
        });
        
        $this->ws->on('message', function (\Ratchet\RFC6455\Messaging\Message $message) {
            $message = $message->getPayload();
            if(!$message) {
                return;
            }
            
            $this->handleMessage($message);
        });
        
        $this->ws->on('error', function (\Throwable $error) {
            $this->emit('error', $error);
        });
        
        $this->ws->on('close', function (int $code, string $reason) {
            $this->ws = null;
            $this->connectPromise = null;
            
            if($this->connectTimer) {
                $this->client->getLoop()->cancelTimer($this->connectTimer);
                $this->connectTimer = null;
            }
            
            if($this->status <= self::STATUS_CONNECTED) {
                $this->status = self::STATUS_DISCONNECTED;
            }
            
            $this->emit('debug', 'Disconnected from node');
            $this->emit('disconnect', $code, $reason, $this->expectedClose);
            
            foreach($this->players as $player) {
                $player->destroy();
            }
            
            if($code === 1000 && $this->expectedClose) {
                $this->status = self::STATUS_IDLE;
                return;
            }
            
            $this->status = self::STATUS_RECONNECTING;
            $this->renewConnection(true);
        });
        
        $this->emit('debug', 'Connected to node');
    }
    
    /**
     * Handles the websocket message.
     * @param string  $payload
     * @return void
     */
    protected function handleMessage(string $payload) {
        $data = \json_decode($payload, true);
        if($data === false && \json_last_error() !== \JSON_ERROR_NONE) {
            $this->emit('debug', 'Invalid message received');
            return;
        }
        
        if(isset($data['guildId'])) {
            $data['guildId'] = (int) $data['guildId'];
        }
        
        switch(($data['op'] ?? null)) {
            case 'playerUpdate':
                $player = $this->players->get($data['guildId']);
                if(!$player) {
                    $this->emit('debug', 'Unexpected playerUpdate for unknown player for guild '.($data['guildId'] ?? ''));
                    return;
                }
                
                $player->updateState($data['state']);
            break;
            case 'stats':
                if($this->stats) {
                    $this->stats->update($data);
                } else {
                    $this->stats = new \CharlotteDunois\Luna\RemoteStats($this->node, $data);
                }
                
                $this->emit('stats', $this->stats);
            break;
            case 'event':
                $this->handleEvent($data);
            break;
            default:
                $this->emit('debug', 'Unexpected message op: '.($data['op'] ?? ''));
            break;
        }
    }
    
    /**
     * Handles the websocket event.
     * @param array  $data
     * @return void
     */
    protected function handleEvent(array $data) {
        $player = $this->players->get($data['guildId']);
        if(!$player) {
            return;
        }
        
        $track = $data['track'];
        if($player->track && $player->track->track === $track) {
            $track = $player->track;
        }
        
        switch(($data['type'] ?? null)) {
            case 'TrackEndEvent':
                $mayStartNext = \in_array($data['reason'], \CharlotteDunois\Luna\AudioTrack::AUDIO_END_REASON_CONTINUE);
                $player->emit('end', $track, $data['reason'], $mayStartNext);
            break;
            case 'TrackExceptionEvent':
                $player->emit('error', $track, (new \CharlotteDunois\Luna\RemoteTrackException($data['error'])));
            break;
            case 'TrackStuckEvent':
                $player->emit('stuck', $track, ((int) $data['thresholdMs']));
            break;
            default:
                $this->emit('debug', 'Unexpected event type: '.($data['type'] ?? ''));
            break;
        }
    }
}
