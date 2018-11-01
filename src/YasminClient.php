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
 * The Lavalink client for Yasmin. This class interacts with Yasmin to do all the updates for you.
 * If you get disconnected from the Discord Gateway, then all players will be destroyed (which is a consequence of the disconnect).
 *
 * @property \CharlotteDunois\Yasmin\Client       $client       The yasmin client.
 * @property \CharlotteDunois\Collect\Collection  $connections  The open connections, mapped by guild ID (as int) to players.
 */
class YasminClient extends Client {
    /**
     * The Yasmin client.
     * @var \CharlotteDunois\Yasmin\Client
     */
    protected $client;
    
    /**
     * The open connections, mapped by guild ID (as int) to players.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $connections;
    
    /**
     * Whether we are ready.
     * @var bool
     */
    protected $ready = false;
    
    /**
     * Yasmin listeners.
     * @var \Closure[]
     */
    protected $yasminListeners = array();
    
    /**
     * Scheduled voice state updates.
     * @var array
     */
    protected $scheduledVoiceStates = array();
    
    /**
     * Constructor.
     *
     * Additional options:
     * ```
     * array(
     *     'disableDisconnectListener' => bool, (disables the disconnect (& reconnect) listener, which destroys all players in the client -
     *                                            they get automatically destroyed by Lavalink on the server, if you disable the listener you have to provide your own listener)
     * )
     * ```
     *
     * @param \CharlotteDunois\Yasmin\Client  $client
     * @param array                           $options    Optional options.
     * @see \CharlotteDunois\Luna\Client
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $options = array()) {
        $this->client = $client;
        $this->connections = new \CharlotteDunois\Collect\Collection();
        
        $options['internal.disableBrowser'] = true;
        $numShards = 1;
        $userID = 0;
        
        if($this->client->readyTimestamp !== null) {
            $userID = (int) $this->client->user->id;
            $numShards = (int) $this->client->getOption('shardCount');
            $this->ready = true;
        } else {
            $this->client->once('ready', function () {
                $this->userID = (int) $this->client->user->id;
                $this->numShards = (int) $this->client->getOption('shardCount');
                $this->ready = true;
            });
        }
        
        parent::__construct($client->getLoop(), $userID, $numShards, $options);
        
        $this->addListeners();
    }
    
    /**
     * Removes all listeners from Yasmin.
     * @return void
     */
    function destroy() {
        foreach($this->yasminListeners as $name => $cl) {
            $this->client->removeListener($name, $cl);
        }
    }
    
    /**
     * Starts all connections to the nodes. Can only be called **after** Yasmin turned ready.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException
     */
    function start() {
        if($this->userID === 0) {
            throw new \BadMethodCallException('Can not start nodes before Yasmin turned ready');
        }
        
        if($this->ready) {
            return parent::start();
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->loop->futureTick(function () use ($resolve, $reject) {
                $this->start()->done($resolve, $reject);
            });
        }));
    }
    
    /**
     * Joins a voice channel. The guild region will be stripped down to `eu`, `us`, etc. Resolves with an instance of Player.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @param \CharlotteDunois\Luna\Link|null              $link     The node to use, or automatically determine one.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException  Thrown when the client is not ready.
     * @throws \LogicException          Thrown when we have insufficient permissions.
     * @see \CharlotteDunois\Luna\Player
     */
    function joinChannel(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel, ?\CharlotteDunois\Luna\Link $link = null) {
        if($this->client->readyTimestamp === null) {
            throw new \BadMethodCallException('Client is not ready yet');
        }
        
        if($this->connections->has($channel->guild->id)) {
            return \React\Promise\resolve($this->connections->get($channel->guild->id));
        }
        
        $this->checkPermissions($channel);
        
        if(!$link) {
            $region = \str_replace('vip-', '', $channel->guild->region);
            $region = \substr($region, 0, (\strpos($region, '-') ?: \strlen($region)));
            
            if($this->loadBalancer) {
                $link = $this->loadBalancer->getIdealNode($region);
            } else {
                $link = $this->getIdealNode($region);
            }
        }
        
        $vstf = function (\CharlotteDunois\Yasmin\Models\GuildMember $new, ?\CharlotteDunois\Yasmin\Models\GuildMember $old) use (&$channel) {
            return ($new->id === $this->client->user->id && $new->voiceChannelID === $channel->id && $new->voiceSessionID !== null);
        };
        
        $vsef = function (array $data) use (&$channel) {
            return (((int) $data['guild_id']) === ((int) $channel->guild->id) && ($data['endpoint'] ?? null) !== null);
        };
        
        $opts = array(
            'time' => 30
        );
        
        $voiceState = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, 'voiceStateUpdate', $vstf, $opts);
        $voiceServer = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, 'voiceServerUpdate', $vsef, $opts);
        
        $shard = $this->client->shards->get($channel->guild->shardID);
        $shard->ws->send(array(
            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
            'd' => array(
                'guild_id' => $channel->guild->id,
                'channel_id' => $channel->id,
                'self_deaf' => $channel->guild->me->selfDeaf,
                'self_mute' => $channel->guild->me->selfMute
            )
        ));
        
        return \React\Promise\all(array($voiceState, $voiceServer))->then(function ($events) use (&$channel, &$link) {
            $player = $link->createPlayer(((int) $channel->guild->id), $events[0][0]->voiceSessionID, $events[1][0]);
            
            $player->on('destroy', function () use (&$player) {
                $this->connections->delete($player->guildID);
            });
            
            $this->connections->set($player->guildID, $player);
            return $player;
        }, function (\Throwable $error) use (&$channel) {
            $this->leaveChannel($channel)->done();
            throw $error;
        });
    }
    
    /**
     * Leaves a voice channel and destroys any existing player.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException  Thrown when the client is not ready.
     */
    function leaveChannel(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel) {
        if($this->client->readyTimestamp === null) {
            throw new \BadMethodCallException('Client is not ready yet');
        }
        
        if($this->connections->has($channel->guild->id)) {
            $player = $this->connections->get($channel->guild->id);
            $this->connections->delete($channel->guild->id);
            
            $player->destroy();
        }
        
        $shard = $this->client->shards->get($channel->guild->shardID);
        return $shard->ws->send(array(
            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
            'd' => array(
                'guild_id' => $channel->guild->id,
                'channel_id' => null,
                'self_deaf' => $channel->guild->me->selfDeaf,
                'self_mute' => $channel->guild->me->selfMute
            )
        ));
    }
    
    /**
     * Moves to a different voice channel in the same guild.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException    Thrown when the client is not ready.
     * @throws \InvalidArgumentException  Thrown when there is connection for the guild.
     * @throws \LogicException            Thrown when we have insufficient permissions.
     */
    function moveToChannel(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel) {
        if($this->client->readyTimestamp === null) {
            throw new \BadMethodCallException('Client is not ready yet');
        }
        
        if(!$this->connections->has($channel->guild->id)) {
            throw new \InvalidArgumentException('No open voice connection to that guild');
        }
        
        if($channel->id === $channel->guild->me->voiceChannelID) {
            return \React\Promise\resolve();
        }
        
        $perms = $channel->permissionsFor($channel->guild->me);
        
        if(!$perms->has('CONNECT') && !$perms->has('MOVE_MEMBERS')) {
            throw new \LogicException('Insufficient permissions to join the voice channel');
        }
        
        if($channel->members->count() >= $channel->userLimit && !$perms->has('MOVE_MEMBERS')) {
            throw new \LogicException('Voice channel user limit reached, unable to join the voice channel');
        }
        
        if(!$perms->has('SPEAK')) {
            throw new \LogicException('We can not speak in the voice channel, joining makes no sense');
        }
        
        $shard = $this->client->shards->get($channel->guild->shardID);
        return $shard->ws->send(array(
            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
            'd' => array(
                'guild_id' => $channel->guild->id,
                'channel_id' => $channel->id,
                'self_deaf' => $channel->guild->me->selfDeaf,
                'self_mute' => $channel->guild->me->selfMute
            )
        ));
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     * @internal
     */
    function createHTTPRequest(string $method, string $url, array $headers, string $body = null) {
        $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $body);
        return \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequest($request);
    }
    
    /**
     * Checks the necessary permissions for the channel.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @return void
     */
    protected function checkPermissions(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel) {
        $perms = $channel->permissionsFor($channel->guild->me);
        
        if(!$perms->has('CONNECT') && !$perms->has('MOVE_MEMBERS')) {
            throw new \LogicException('Insufficient permissions to join the voice channel');
        }
        
        if($channel->userLimit > 0 && $channel->members->count() >= $channel->userLimit && !$perms->has('MOVE_MEMBERS')) {
            throw new \LogicException('Voice channel user limit reached, unable to join the voice channel');
        }
        
        if(!$perms->has('SPEAK')) {
            throw new \LogicException('We can not speak in the voice channel, joining makes no sense');
        }
    }
    
    /**
     * Adds listeners.
     * @return void
     */
    protected function addListeners() {
        if($this->getOption('disableDisconnectListener', false) !== true) {
            $disconnect = function (\CharlotteDunois\Yasmin\Models\Shard $shard) {
                $this->emit('debug', null, 'Yasmin Shard '.$shard->id.' got disconnected from Discord, destroying all shard players...');
                
                foreach($this->links as $link) {
                    foreach($link->players as $guildID => $player) {
                        $guild = $this->client->guilds->get($guildID);
                        
                        if($guild->shardID === $shard->id) {
                            $this->scheduledVoiceStates[] = $guildID;
                            $player->destroy();
                            
                            $this->connections->delete($guildID);
                        }
                    }
                }
            };
            
            $this->yasminListeners['disconnect'] = $disconnect;
            $this->client->on('disconnect', $disconnect);
            
            $reconnect = function (\CharlotteDunois\Yasmin\Models\Shard $shard) {
                while($guildID = \array_shift($this->scheduledVoiceStates)) {
                    $guildID = (string) $guildID;
                    
                    if(!$this->client->guilds->has($guildID)) {
                        continue;
                    }
                    
                    $guild = $this->client->guilds->get($guildID);
                    
                    if($guild->shardID === $shard->id) {
                        $this->emit('debug', null, 'Yasmin Shard '.$shard->id.' reconnected to Discord, sending voice state update to disconnect for guild '.$guildID);
                        
                        $shard->ws->send(array(
                            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
                            'd' => array(
                                'guild_id' => $guildID,
                                'channel_id' => null,
                                'self_deaf' => $guild->me->selfDeaf,
                                'self_mute' => $guild->me->selfMute
                            )
                        ));
                    }
                }
            };
            
            $this->yasminListeners['reconnect'] = $reconnect;
            $this->client->on('reconnect', $reconnect);
        }
        
        $guildDelete = function (\CharlotteDunois\Yasmin\Models\Guild $guild) {
            if($this->connections->has($guild->id)) {
                $this->connections->get($guild->id)->destroy();
                $this->connections->delete($guild->id);
            }
        };
        
        $this->yasminListeners['guildDelete'] = $guildDelete;
        $this->client->on('guildDelete', $guildDelete);
        
        $voiceServerUpdate = function (array $data) {
            $guild = $this->client->guilds->get(($data['guild_id'] ?? null));
            
            if($guild instanceof \CharlotteDunois\Yasmin\Models\Guild && $this->connections->has($guild->id)) {
                $link = $this->connections->get($guild->id)->link;
                
                foreach($link->players as $player) {
                    $player->sendVoiceUpdate($player->voiceServerUpdate['sessionID'], $data);
                }
            }
        };
        
        $this->yasminListeners['voiceServerUpdate'] = $voiceServerUpdate;
        $this->client->on('voiceServerUpdate', $voiceServerUpdate);
        
        $this->on('failover', function (\CharlotteDunois\Luna\Link $link, \CharlotteDunois\Luna\Player $player) {
            $this->connections->set($player->guildID, $player);
        });
    }
}
