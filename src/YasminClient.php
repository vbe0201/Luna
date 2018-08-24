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
 * The Lavalink Client for Yasmin. This class interacts with Yasmin to do all the updates for you.
 * @property \CharlotteDunois\Yasmin\Client            $client       The yasmin client.
 * @property \CharlotteDunois\Yasmin\Utils\Collection  $connections  The open connections, mapped by guild ID (as int) to players.
 */
class YasminClient extends Client {
    /**
     * The Yasmin client.
     * @var \CharlotteDunois\Yasmin\Client
     */
    protected $client;
    
    /**
     * The open connections, mapped by guild ID to players.
     * @var \CharlotteDunois\Yasmin\Utils\Collection
     */
    protected $connections;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Yasmin\Client  $client
     * @param int                             $numShards  The amount of shards the bot has.
     * @param array                           $options    Optional options.
     * @see \CharlotteDunois\Luna\Client
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, int $numShards = 1, array $options = array()) {
        $this->client = $client;
        $this->connections = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $options['internal.disableBrowser'] = true;
        $userID = 0;
        
        if($this->client->readyTimestamp !== null) {
            $userID = (int) $this->client->user->id;
        } else {
            $this->client->once('ready', function () {
                $this->userID = (int) $this->client->user->id;
            });
        }
        
        $this->on('failover', function (\CharlotteDunois\Luna\Node $node, \CharlotteDunois\Luna\Player $newPlayer) {
            $this->connections->set($newPlayer->guildID, $newPlayer);
        });
        
        parent::__construct($client->getLoop(), $userID, $numShards, $options);
    }
    
    /**
     * Joins a voice channel. The guild region will be stripped down to `eu`, `us`, etc. Resolves with an instance of Player.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @param \CharlotteDunois\Luna\Node|null              $node     The node to use, or automatically determine one.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException  Thrown when the client is not ready.
     * @throws \LogicException          Thrown when we have insufficient permissions.
     * @see \CharlotteDunois\Luna\Player
     */
    function joinChannel(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel, ?\CharlotteDunois\Luna\Node $node = null) {
        if($this->client->readyTimestamp === null) {
            throw new \BadMethodCallException('Client is not ready yet');
        }
        
        if($this->connections->has($channel->guild->id)) {
            return \React\Promise\resolve($this->connections->get($channel->guild->id));
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
        
        if(!$node) {
            $loadbalancer = $this->getOption('loadbalancer');
            
            $region = \str_replace('vip-', '', $channel->guild->region);
            $region = \substr($region, 0, (\strpos($region, '-') ?: \strlen($region)));
            
            if($loadbalancer instanceof \CharlotteDunois\Luna\LoadBalancer) {
                $node = $loadbalancer->getIdealNode($region);
            } else {
                $node = $this->getIdealNode($region);
            }
        }
        
        $vstf = function (\CharlotteDunois\Yasmin\Models\GuildMember $new, ?\CharlotteDunois\Yasmin\Models\GuildMember $old) use (&$channel) {
            return ($new->id === $this->client->user->id && $new->voiceChannelID === $channel->id && $new->voiceSessionID !== null);
        };
        
        $vsef = function (array $data) use (&$channel) {
            return (((int) $data['guild_id']) === ((int) $channel->guild->id));
        };
        
        $opts = array(
            'time' => 30
        );
        
        if(\version_compare(\CharlotteDunois\Yasmin\Client::VERSION, '0.4.3-dev') >= 0) {
            $name = 'voiceServerUpdate';
        } else {
            $name = 'self.voiceServerUpdate';
        }
        
        $voiceState = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, 'voiceStateUpdate', $vstf, $opts);
        $voiceServer = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, $name, $vsef, $opts);
        
        $this->client->wsmanager()->send(array(
            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
            'd' => array(
                'guild_id' => $channel->guild->id,
                'channel_id' => $channel->id,
                'self_deaf' => $channel->guild->me->selfDeaf,
                'self_mute' => $channel->guild->me->selfMute
            )
        ));
        
        return \React\Promise\all(array($voiceState, $voiceServer))->then(function ($events) use (&$channel, &$node) {
            $player = $node->sendVoiceUpdate(((int) $channel->guild->id), $events[0][0]->voiceSessionID, $events[1][0]);
            
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
        
        return $this->client->wsmanager()->send(array(
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
        
        return $this->client->wsmanager()->send(array(
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
}
