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
 * This interface documents all events emitted on the client. Events emitted on the links are re-emitted on the client, with the additional argument `$link`, (as such documented here).
 * Player events are however **not** emitted on the client.
 */
interface ClientEvents {
    /**
     * Debug messages.
     * @param \CharlotteDunois\Luna\Link|null  $link
     * @param string|\Exception                $message
     * @return void
     */
    function debug(?\CharlotteDunois\Luna\Link $link, $message);
    
    /**
     * Emitted when an error happens. You should always listen on this event.
     * @param \CharlotteDunois\Luna\Link|null  $link
     * @param \Throwable                       $error
     * @return void
     */
    function error(?\CharlotteDunois\Luna\Link $link, \Throwable $error);
    
    /**
     * Emitted when the node gets disconnected.
     * @param \CharlotteDunois\Luna\Link|null  $link
     * @param int                              $code
     * @param string                           $reason
     * @param bool                             $expectedClose
     * @return void
     */
    function disconnect(?\CharlotteDunois\Luna\Link $link, int $code, string $reason, bool $expectedClose);
    
    /**
     * Emitted when a failover happens. Only emitted on the client.
     * @param \CharlotteDunois\Luna\Link|null  $link
     * @param \CharlotteDunois\Luna\Player     $player
     * @return void
     */
    function failover(?\CharlotteDunois\Luna\Link $link, \CharlotteDunois\Luna\Player $player);
    
    /**
     * Emitted when a new player gets created.
     * @param \CharlotteDunois\Luna\Link|null  $link
     * @param \CharlotteDunois\Luna\Player     $player
     * @return void
     */
    function newPlayer(?\CharlotteDunois\Luna\Link $link, \CharlotteDunois\Luna\Player $player);
    
    /**
     * Emitted when the node gets stats from the lavalink node.
     * @param \CharlotteDunois\Luna\Link|null    $link
     * @param \CharlotteDunois\Luna\RemoteStats  $stats
     * @return void
     */
    function stats(?\CharlotteDunois\Luna\Link $link, \CharlotteDunois\Luna\RemoteStats $stats);
}
