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
 * @property \CharlotteDunois\Luna\Client  $client  The Luna client.
 * @property \CharlotteDunois\Luna\Node    $node    The node this link is for.
 * @property int                           $status  The connection status.
 */
class Link {
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
        $this->connector = new \Ratchet\Client\Connector($client->getLoop(), $client->getOption('connector'));
    }
    
    /**
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
     * @return mixed
     * @throws \RuntimeException
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
        
        $this->node->emit('debug', 'Connecting to node');
        $this->connectAttempts++;
        
        $connector = $this->connector;
        $this->connectPromise = $connector($this->node->wsHost, array(), array(
            'Authorization' => $this->node->password,
            'Num-Shards' => $this->client->numShards,
            'User-Id' => $this->client->userID
        ))->then(function (\Ratchet\Client\WebSocket $conn) {
            $this->setupWebsocket($conn);
        }, function (\Throwable $error) {
            $this->node->emit('error', $error);
            
            if($this->ws) {
                $this->ws->close(1006);
            }
            
            $maxAttempts = $this->client->getOption('node.maxConnectAttempts');
            if($maxAttempts > 0 && $maxAttempts <= $this->connectAttempts) {
                $this->status = self::STATUS_IDLE;
                
                $this->node->emit('debug', 'Reached maximum connect attempts');
                return;
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
                $this->node->emit('debug', 'Error reconnecting after failed connection attempt... retrying in 30 seconds');
                return $this->scheduleConnect();
            });
        }
        
        $this->node->emit('debug', 'Scheduling reconnection for executing in 30 seconds');
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
        
        $this->node->emit('debug', 'Sending WS packet');
        $this->ws->send(\json_encode($packet));
    }
    
    /**
     * Sets up the websocket. Resolver for the connector.
     * @param \Ratchet\Client\WebSocket  $conn
     * @return void
     */
    protected function setupWebsocket(\Ratchet\Client\WebSocket $conn) {
        if(!$conn->response->hasHeader('Lavalink-Major-Version') || $conn->response->getHeader('Lavalink-Major-Version')[0] < 3) {
            throw new \RuntimeException('The Lavalink Server major version is below v3.0');
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
            $this->node->emit('error', $error);
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
            
            $this->node->emit('debug', 'Disconnected from node');
            $this->node->emit('disconnect', $code, $reason, $this->expectedClose);
            
            foreach($this->node->players as $player) {
                $player->destroy();
            }
            
            if($code === 1000 && $this->expectedClose) {
                $this->status = self::STATUS_IDLE;
                return;
            }
            
            $this->status = self::STATUS_RECONNECTING;
            $this->renewConnection(true);
        });
        
        $this->node->emit('debug', 'Connected to node');
    }
    
    /**
     * Handles the websocket message.
     * @param string  $payload
     * @return void
     */
    protected function handleMessage(string $payload) {
        $data = \json_decode($payload, true);
        if($data === false && \json_last_error() !== \JSON_ERROR_NONE) {
            $this->node->emit('debug', 'Invalid message received');
            return;
        }
        
        if(isset($data['guildId'])) {
            $data['guildId'] = (int) $data['guildId'];
        }
        
        switch(($data['op'] ?? null)) {
            case 'playerUpdate':
                $player = $this->node->players->get($data['guildId']);
                if(!$player) {
                    $this->node->emit('debug', 'Unexpected playerUpdate for unknown player for guild '.($data['guildId'] ?? ''));
                    return;
                }
                
                $player->updateState($data['state']);
            break;
            case 'stats':
                $this->node->emit('stats', $this->node->updateStats($data));
            break;
            case 'event':
                $this->handleEvent($data);
            break;
            default:
                $this->node->emit('debug', 'Unexpected message op: '.($data['op'] ?? ''));
            break;
        }
    }
    
    /**
     * Handles the websocket event.
     * @param array  $data
     * @return void
     */
    protected function handleEvent(array $data) {
        $player = $this->node->players->get($data['guildId']);
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
                $this->node->emit('debug', 'Unexpected event type: '.($data['type'] ?? ''));
            break;
        }
    }
}
