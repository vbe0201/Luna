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
 * Represents a player of a guild on a node.
 */
class Player implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The node this player is on.
     * @var \CharlotteDunois\Luna\Node
     */
    protected $node;
    
    /**
     * The guild ID this player is serving.
     * @var int
     */
    protected $guildID;
    
    /**
     * The currently playing audio track.
     * @var \CharlotteDunois\Luna\AudioTrack
     */
    protected $track;
    
    /**
     * Whether the track is currently paused.
     * @var bool
     */
    protected $paused = false;
    
    /**
     * The volume from 0 to 100(%).
     * @var int
     */
    protected $volume = 100;
    
    /**
     * The timestamp of the last update.
     * @var int
     */
    protected $updateTime = -1;
    
    /**
     * The current position of the track, in milliseconds.
     * @var int
     */
    protected $position = -1;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Luna\Node  $node
     * @param int                         $guildID
     */
    function __construct(\CharlotteDunois\Luna\Node $node, int $guildID) {
        $this->node = $node;
        $this->guildID = $guildID;
    }
    
    /**
     * Plays a track.
     * @param \CharlotteDunois\Luna\AudioTrack  $track
     * @return void
     * @throws \RuntimeException
     */
    function playTrack(\CharlotteDunois\Luna\AudioTrack $track) {
        $packet = array(
            'op' => 'play',
            'guildId' => $this->guildID,
            'track' => $track->track,
            'startTime' => $this->position,
            'pause' => $this->paused,
            'volume' => $this->volume
        );
        
        $this->node->link->send($packet);
        
        $this->updateTime = \time();
        $this->track = $track;
        
        $this->emit('start', $track);
    }
    
    /**
     * Changes the node. Used when we are moved to a new socket.
     * @return void
     * @internal
     */
    function changeNode() {
        if($this->track) {
            $time = \time() - $this->updateTime;
            $this->track->setPosition(\min($time, $this->track->duration));
            $this->playTrack($this->track);
        }
    }
    
    /**
     * Updates the state.
     * @param array  $state
     * @return void
     * @internal
     */
    function updateState(array $state) {
        $this->updateTime = (int) (((int) $state['updateTime']) / 1000);
        $this->position  = (int) $state['position'];
    }
}
