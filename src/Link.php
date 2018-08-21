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
     * @var \Ratchet\Client\Connector
     */
    protected $connector;
    
    /**
     * @var \Ratchet\Client\WebSocket
     */
    protected $ws;
    
    /**
     * Whether we expected the ws to close.
     * @var bool
     */
    protected $expectedClose = false;
    
    /**
     * The WS connection status
     * @var int
     */
    protected $wsStatus = self::STATUS_DISCONNECTED;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Luna\Client  $client
     * @param \CharlotteDunois\Luna\Node    $node
     * @param \React\Socket\Connector|null  $connector
     */
    function __construct(\CharlotteDunois\Luna\Client $client, \CharlotteDunois\Luna\Node $node, ?\React\Socket\Connector $connector = null) {
        $this->client = $client;
        $this->node = $node;
        $this->connector = new \Ratchet\Client\Connector($client->getLoop(), $connector);
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
        if($this->ws) {
            return \React\Promise\resolve();
        }
        
        $this->expectedClose = false;
        
        $this->emit('debug', 'Connecting to node');
        
        if($this->wsStatus < self::STATUS_CONNECTING || $this->wsStatus > self::STATUS_RECONNECTING) {
            $this->wsStatus = self::STATUS_CONNECTING;
        }
        
        $connector = $this->connector;
        return $connector($this->node->wsHost, array(), array(
            'Authorization' => $this->node->password,
            'Num-Shards' => $this->client->numShards,
            'User-Id' => $this->client->userID
        ))->done(function (\Ratchet\Client\WebSocket $conn) {
            $this->ws = &$conn;
            $this->wsStatus = self::STATUS_NEARLY;
            
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
                
                if($this->wsStatus <= self::STATUS_CONNECTED) {
                    $this->wsStatus = self::STATUS_DISCONNECTED;
                }
                
                $this->emit('debug', 'Disconnected from node');
                $this->emit('disconnect', $code, $reason);
                
                if($code === 1000 && $this->expectedClose) {
                    $this->wsStatus = self::STATUS_IDLE;
                    return;
                }
                
                $this->wsStatus = self::STATUS_RECONNECTING;
                $this->renewConnection();
            });
            
            $this->client->emit('debug', 'Connected to node');
        }, function (\Throwable $error) {
            $this->emit('error', $error);
            
            if($this->ws) {
                $this->ws->close(1006);
            }
            $this->wsStatus = self::STATUS_DISCONNECTED;
            return $this->renewConnection();
        });
    }
    
    /**
     * Closes the connection to the node websocket.
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
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function renewConnection() {
        return $this->connect()->otherwise(function () {
            $this->emit('debug', 'Error reconnecting after failed connection attempt... retrying in 30 seconds');
            
            return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($forceNewGateway) {
                $this->client->addTimer(30, function () use ($resolve, $reject) {
                    $this->renewConnection()->done($resolve, $reject);
                });
            }));
        });
    }
    
    /**
     * Sends a packet.
     * @param array $packet
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \RuntimeException
     */
    function send(array $packet) {
        if($this->wsStatus !== self::STATUS_NEARLY && $this->wsStatus !== self::STATUS_CONNECTED) {
            throw new \RuntimeException('Unable to send WS message before a WS connection is established');
        }
        
        $this->emit('debug', 'Sending WS packet');
        $this->ws->send(\json_encode($packet));
    }
    
    /**
     * Handles the websocket message.
     * @param string  $payload
     * @return void
     */
    protected function handleMessage(string $payload) {
        $data = \json_decode($payload);
        if($data === false && \json_last_error() !== \JSON_ERROR_NONE) {
            $this->emit('debug', 'Invalid message received');
            return;
        }
        
        switch(($data['op'] ?? null)) {
            case 'playerUpdate':
                $this->node->player->updateState($data);
            break;
            case 'stats':
                $stats = new \CharlotteDunois\Luna\RemoteStats($data);
                $this->emit('stats', $stats);
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
        switch(($data['type'] ?? null)) {
            case 'TrackEndEvent':
            break;
            case 'TrackExceptionEvent':
            break;
            case 'TrackStuckEvent':
            break;
            default:
                $this->emit('debug', 'Unexpected event type: '.($data['type'] ?? ''));
            break;
        }
    }
}
