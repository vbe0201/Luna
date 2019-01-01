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
 * Represents a node's stats. The lavalink node sends every minute stats, which updates any existing instances.
 *
 * @property \CharlotteDunois\Luna\Node  $node              The node these stats are for.
 * @property int                         $players           How many players the node is running.
 * @property int                         $playingPlayers    How many players are currently playing.
 * @property int                         $uptime            The uptime of the node in seconds.
 * @property int                         $memoryFree        Free memory in bytes.
 * @property int                         $memoryUsed        Used memory in bytes.
 * @property int                         $memoryAllocated   Allocated memory in bytes.
 * @property int                         $memoryReservable  Reservable memory in bytes.
 * @property int                         $cpuCores          The number of cpu cores.
 * @property float                       $systemload        The system load.
 * @property float                       $lavalinkLoad      The lavalink load.
 * @property int|null                    $framesSent        Average frames sent per minute.
 * @property int|null                    $framesNulled      Average frames nulled per minute.
 * @property int|null                    $framesDeficit     Average frames deficit per minute.
 */
class RemoteStats {
    /**
     * The node these stats are for.
     * @var \CharlotteDunois\Luna\Node
     */
    protected $node;
    
    /**
     * How many players the node is running.
     * @var int
     */
    protected $players;
    
    /**
     * How many players are currently playing.
     * @var int
     */
    protected $playingPlayers;
    
    /**
     * The uptime of the node in seconds.
     * @var int
     */
    protected $uptime;
    
    /**
     * Free memory in bytes.
     * @var int
     */
    protected $memoryFree;
    
    /**
     * Used memory in bytes.
     * @var int
     */
    protected $memoryUsed;
    
    /**
     * Allocated memory in bytes.
     * @var int
     */
    protected $memoryAllocated;
    
    /**
     * Reservable memory in bytes.
     * @var int
     */
    protected $memoryReservable;
    
    /**
     * Number of CPU cores.
     * @var int
     */
    protected $cpuCores;
    
    /**
     * The system load.
     * @var float
     */
    protected $systemLoad;
    
    /**
     * The lavalink load.
     * @var float
     */
    protected $lavalinkLoad;
    
    /**
     * Average frames sent per minute.
     * @var int|null
     */
    protected $framesSent;
    
    /**
     * Average frames nulled per minute.
     * @var int|null
     */
    protected $framesNulled;
    
    /**
     * Average frames deficit per minute.
     * @var int|null
     */
    protected $framesDeficit;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Luna\Node  $node
     * @param array                       $stats
     */
    function __construct(\CharlotteDunois\Luna\Node $node, array $stats) {
        $this->node = $node;
        $this->update($stats);
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
     * Updates the stats.
     * @param array  $stats
     * @return void
     */
    function update(array $stats) {
        $this->players = (int) $stats['players'];
        $this->playingPlayers = (int) $stats['playingPlayers'];
        $this->uptime = (int) ($stats['uptime'] / 1000);
        
        $this->memoryFree = (int) $stats['memory']['free'];
        $this->memoryUsed = (int) $stats['memory']['used'];
        $this->memoryAllocated = (int) $stats['memory']['allocated'];
        $this->memoryReservable = (int) $stats['memory']['reservable'];
        
        $this->cpuCores = (int) $stats['cpu']['cores'];
        $this->systemLoad = (float) $stats['cpu']['systemLoad'];
        $this->lavalinkLoad = (float) $stats['cpu']['lavalinkLoad'];
        
        if(!empty($stats['frameStats'])) {
            $this->framesSent = (int) $stats['frameStats']['sent'];
            $this->framesNulled = (int) $stats['frameStats']['nulled'];
            $this->framesDeficit = (int) $stats['frameStats']['deficit'];
        } else {
            $this->framesSent = null;
            $this->framesNulled = null;
            $this->framesDeficit = null;
        }
    }
}
