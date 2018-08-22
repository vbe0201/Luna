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
 * Represents a node's stats.
 */
class RemoteStats {
    protected $players;
    protected $playingPlayers;
    protected $uptime; //in millis

    // In bytes
    protected $memFree;
    protected $memUsed;
    protected $memAllocated;
    protected $memReservable;

    protected $cpuCores;
    protected $systemLoad;
    protected $lavalinkLoad;

    protected $avgFramesSentPerMinute = -1;
    protected $avgFramesNulledPerMinute = -1;
    protected $avgFramesDeficitPerMinute = -1;
}
