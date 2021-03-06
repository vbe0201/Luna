<?php
/**
 * Luna
 * Copyright 2018-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Luna/blob/master/LICENSE
*/

namespace CharlotteDunois\Luna;

/**
 * Represents a player of a guild on a node.
 *
 * @property \CharlotteDunois\Luna\Link             $link         The link this player is connected to.
 * @property int                                    $guildID      The guild ID this player is serving.
 * @property \CharlotteDunois\Luna\AudioTrack|null  $track        The currently playing audio track.
 * @property bool                                   $paused       Whether the track is currently paused.
 * @property int                                    $position     The position of the track in milliseconds.
 * @property int                                    $volume       The volume of the player from 0 to 1000(%).
 * @property array                                  $voiceUpdate  The sent voice update event.
 * @see \CharlotteDunois\Luna\PlayerEvents
 */
class Player implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The link this player is connected to.
     * @var \CharlotteDunois\Luna\Link
     */
    protected $link;
    
    /**
     * The guild ID this player is serving.
     * @var int
     */
    protected $guildID;
    
    /**
     * The currently playing audio track.
     * @var \CharlotteDunois\Luna\AudioTrack|null
     */
    protected $track;
    
    /**
     * Whether the track is currently paused.
     * @var bool
     */
    protected $paused = false;
    
    /**
     * The current position of the track in milliseconds.
     * @var int
     */
    protected $position = 0;
    
    /**
     * The volume of the player from 0 to 1000(%).
     * @var int
     */
    protected $volume = 100;
    
    /**
     * The timestamp of the last update in milliseconds.
     * @var float
     */
    protected $updateTime = -1;
    
    /**
     * The sent voice update event.
     * @var array|null
     */
    protected $voiceUpdate;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Luna\Link  $link
     * @param int                         $guildID
     */
    function __construct(\CharlotteDunois\Luna\Link $link, int $guildID) {
        $this->link = $link;
        $this->guildID = $guildID;
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
     * Plays a track.
     * @param \CharlotteDunois\Luna\AudioTrack  $track
     * @param int                               $startTime  The start time in milliseconds to seek to.
     * @param int                               $endTime    The end time when to stop playing in milliseconds.
     * @return void
     * @throws \RuntimeException
     */
    function play(\CharlotteDunois\Luna\AudioTrack $track, int $startTime = 0, int $endTime = 0) {
        $packet = array(
            'op' => 'play',
            'guildId' => ((string) $this->guildID),
            'track' => $track->track,
            'volume' => $this->volume
        );
        
        if($startTime > 0) {
            $packet['startTime'] = $startTime;
        }
        
        if($endTime > 0) {
            $packet['endTime'] = $endTime;
        }
        
        $this->link->send($packet);
        $this->emit('debug', 'Started playing track "'.($track->author ? $track->author.' - ' : '').$track->title.'"');
        
        $this->paused = false;
        $this->position = $startTime;
        $this->track = $track;
        $this->updateTime = \microtime(true);
        
        $this->emit('start', $track);
    }
    
    /**
     * Stops playing a track.
     * @return void
     * @throws \RuntimeException
     */
    function stop() {
        if($this->track) {
            $packet = array(
                'op' => 'stop',
                'guildId' => ((string) $this->guildID)
            );
            
            $this->link->send($packet);
            $this->emit('debug', 'Stopped music playback');
            
            $this->paused = false;
            $this->position = 0;
            $this->track = null;
            $this->updateTime = \microtime(true);
            
            $this->emit('stop');
        }
    }
    
    /**
     * Destroys the player.
     * @return void
     * @throws \RuntimeException
     */
    function destroy() {
        if($this->link) {
            $packet = array(
                'op' => 'destroy',
                'guildId' => ((string) $this->guildID)
            );
            
            try {
                $this->link->send($packet);
            } catch (\RuntimeException $e) {
                /* Continue regardless of error */
            }
            
            $this->track = null;
            $this->link->players->delete($this->guildID);
            
            $this->emit('debug', 'Destroyed music playback');
            
            $this->emit('destroy');
            $this->link = null;
        }
    }
    
    /**
     * Gets the last position of the played track in milliseconds.
     * @return int
     */
    function getLastPosition() {
        $timeDiff = (int) ((\microtime(true) - $this->updateTime) * 1000);
        return \min(($this->position + $timeDiff), $this->track->duration);
    }
    
    /**
     * Seeks the track.
     * @param int  $position
     * @return void
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     */
    function seekTo(int $position) {
        if($this->track) {
            if(!$this->track->seekable) {
                throw new \BadMethodCallException('Track is not seekable');
            }
            
            $pos = \min($position, $this->track->duration);
            
            if($pos !== $this->position) {
                $packet = array(
                    'op' => 'seek',
                    'guildId' => ((string) $this->guildID),
                    'position' => $pos
                );
                
                $this->link->send($packet);
                $this->emit('debug', 'Seeked to position '.$pos.'ms');
                
                $this->position = $pos;
                $this->updateTime = \microtime(true);
            }
        }
    }
    
    /**
     * Sets the paused state of the track.
     * @param bool  $paused
     * @return void
     * @throws \RuntimeException
     */
    function setPaused(bool $paused) {
        if($this->track && $paused !== $this->paused) {
            $packet = array(
                'op' => 'pause',
                'guildId' => ((string) $this->guildID),
                'pause' => $paused
            );
            
            $this->link->send($packet);
            $this->emit('debug', 'Set paused to '.($paused ? 'true' : 'false'));
            
            $this->paused = $paused;
            $this->emit('paused', $this);
        }
    }
    
    /**
     * Sets the volume of the player.
     * @param int  $volume  Any integer in the range of 0..1000.
     * @return void
     * @throws \RuntimeException
     */
    function setVolume(int $volume) {
        $volume = \min(1000, \max(0, $volume)); // Lavaplayer bounds
        
        if($this->track && $volume !== $this->volume) {
            $packet = array(
                'op' => 'volume',
                'guildId' => ((string) $this->guildID),
                'volume' => $volume
            );
            
            $this->link->send($packet);
            $this->emit('debug', 'Set volume to '.$volume);
            
            $this->volume = $volume;
        }
    }
    
    /**
     * Send a voice update event for the player.
     * @param string  $sessionID  The voice session ID.
     * @param array   $event      The voice server update event from Discord.
     * @return void
     * @throws \BadMethodCallException
     */
    function sendVoiceUpdate(string $sessionID, array $event) {
        $packet = array(
            'op' => 'voiceUpdate',
            'guildId' => ((string) $this->guildID),
            'sessionId' => $sessionID,
            'event' => $event
        );
        
        $this->link->emit('debug', 'Sending voice update for guild '.$this->guildID);
        
        $this->link->send($packet);
        $this->setVoiceUpdate(array(
            'sessionID' => $sessionID,
            'event' => $event
        ));
    }
    
    /**
     * Sets the node. Used for the internal failover.
     * @param \CharlotteDunois\Luna\Link  $link
     * @return void
     */
    function setNode(\CharlotteDunois\Luna\Link $link) {
        $this->link->players->delete($this->guildID);
        
        $this->link = $link;
        $this->link->players->set($this->guildID, $this);
    }
    
    /**
     * Sets the internal Voice Server Update array. Used by `sendVoiceUpdate`.
     * @param array $voiceUpdate
     * @return void
     * @internal
     */
    function setVoiceUpdate(array $voiceUpdate) {
        $this->voiceUpdate = $voiceUpdate;
    }
    
    /**
     * Updates the player state. Invoked by Lavalink.
     * @param array  $state
     * @return void
     */
    function updateState(array $state) {
        $this->updateTime = (float) ($state['time'] / 1000);
        $this->position  = (int) $state['position'];
    }
}
