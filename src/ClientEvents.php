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
 * This interface documents all events emitted on the client. Events emitted on the nodes (and added to the client) are re-emitted on the client, with the additional argument `$node`, (as such documented here).
 * Player events are however **not** emitted on the client.
 */
interface ClientEvents {
    /**
     * Emitted when an error happens. You should always listen on this event.
     * @param \CharlotteDunois\Luna\Node|null  $node
     * @param \Throwable                       $error
     * @return void
     */
    function error(?\CharlotteDunois\Luna\Node $node, \Throwable $error);
    
    /**
     * Debug messages.
     * @param \CharlotteDunois\Luna\Node|null  $node
     * @param string|\Exception                $message
     * @return void
     */
    function debug(?\CharlotteDunois\Luna\Node $node, $message);
    
    /**
     * Emitted when the node gets disconnected.
     * @param \CharlotteDunois\Luna\Node|null  $node
     * @param int                              $code
     * @param string                           $reason
     * @param bool                             $expectedClose
     * @return void
     */
    function disconnect(\CharlotteDunois\Luna\Node $node, int $code, string $reason, bool $expectedClose);
    
    /**
     * Emitted when a failover happens. Only emitted on the client.
     * @param \CharlotteDunois\Luna\Node|null  $node
     * @param \CharlotteDunois\Luna\Player     $player
     * @return void
     */
    function failover(\CharlotteDunois\Luna\Node $node, \CharlotteDunois\Luna\Player $player);
    
    /**
     * Emitted when a new player gets created.
     * @param \CharlotteDunois\Luna\Node|null  $node
     * @param \CharlotteDunois\Luna\Player     $player
     * @return void
     */
    function newPlayer(\CharlotteDunois\Luna\Node $node, \CharlotteDunois\Luna\Player $player);
    
    /**
     * Emitted when the node gets stats from the lavalink node.
     * @param \CharlotteDunois\Luna\Node|null    $node
     * @param \CharlotteDunois\Luna\RemoteStats  $stats
     * @return void
     */
    function stats(\CharlotteDunois\Luna\Node $node, \CharlotteDunois\Luna\RemoteStats $stats);
}
