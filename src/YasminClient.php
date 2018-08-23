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
    function __construct(\CharlotteDunois\Yasmin\Client $client, int $numShards, array $options = array()) {
        $this->client = $client;
        $this->connections = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $options['internal.disableBrowser'] = true;
        $userID = 0;
        
        if($this->client->readyTimestamp !== null) {
            $userID = $this->client->user->id;
        } else {
            $this->client->once('ready', function () {
                $this->userID = $this->client->user->id;
            });
        }
        
        $this->on('failover', function (\CharlotteDunois\Luna\Node $node, \CharlotteDunois\Luna\Player $newPlayer) {
            $this->connections->set($newPlayer->guildID, $newPlayer);
        });
        
        parent::__construct($client->getLoop(), $userID, $numShards, $options);
    }
    
    /**
     * Joins a voice channel. Resolves with an instance of Player.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @param \CharlotteDunois\Luna\Node|null              $node     The node to use, or automatically determine one.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException
     * @see \CharlotteDunois\Luna\Player
     */
    function joinVoiceChannel(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel, ?\CharlotteDunois\Luna\Node $node = null) {
        if($this->client->readyTimestamp === null) {
            throw new \BadMethodCallException('Client is not ready yet');
        }
        
        if(!$node) {
            $node = $this->getIdealNode($channel->guild->region);
        }
        
        $vstf = function (\CharlotteDunois\Yasmin\Models\GuildMember $new, ?\CharlotteDunois\Yasmin\Models\GuildMember $old) use (&$channel) {
            return ($new->id === $this->client->id && $new->voiceChannelID === $channel->id && $new->voiceSessionID !== null);
        };
        
        $vsef = function (array $data) use (&$channel) {
            return (((int) $data['guild_id']) === ((int) $channel->guild->id));
        };
        
        $opts = array(
            'time' => 30
        );
        
        if(\version_compare(\CharlotteDunois\Yasmin\Client::VERSION, '0.4.2-dev') >= 0) {
            $name = 'voiceServerUpdate';
        } else {
            $name = 'self.voiceServerUpdate';
        }
        
        $voiceState = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, 'voiceStateUpdate', $vstf, $opts);
        $voiceServer = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, $name, $vsef, $opts);
        
        $this->client->wsmanager()->send(array(
            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
            'guild_id' => $channel->guild->id,
            'channel_id' => $channel->id,
            'self_deaf' => $channel->guild->me->selfDeaf,
            'self_mute' => $channel->guild->me->selfMute
        ));
        
        return \React\Promise\all(array($voiceState, $voiceServer))->then(function ($events) use (&$channel, &$node) {
            return $node->sendVoiceUpdate(((int) $channel->guild->id), $events[0]->voiceSessionID, $events[1])->then(function (\CharlotteDunois\Luna\Player $player) {
                $player->on('destroy', function () use (&$player) {
                    $this->connections->delete($player->guildID);
                });
                
                $this->channels->set($player->guildID);
            });
        });
    }
    
    /**
     * Leaves a voice channel and destroys any existing player.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel  $channel
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException
     */
    function leaveVoiceChannel(\CharlotteDunois\Yasmin\Models\VoiceChannel $channel) {
        if($this->connections->has($channel->guild->id)) {
            $player = $this->connections->get($channel->guild->id);
            $this->connections->delete($channel->guild->id);
            
            $player->destroy();
        }
        
        return $this->client->wsmanager()->send(array(
            'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['VOICE_STATE_UPDATE'],
            'guild_id' => $channel->guild->id,
            'channel_id' => null,
            'self_deaf' => $channel->guild->me->selfDeaf,
            'self_mute' => $channel->guild->me->selfMute
        ));
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     * @internal
     */
    function createHTTPRequest(string $method, string $url, array $headers, string $body = null) {
        $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $body, '1.1');
        return \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequest($request);
    }
}
