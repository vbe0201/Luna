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
 * This interface documents all events emitted on the player.
 */
interface PlayerEvents {
    /**
     * Emitted when the track ends.
     * @param \CharlotteDunois\Luna\AudioTrack|string  $track
     * @param string                                   $reason
     * @param bool                                     $mayStartNext  See the `see` section.
     * @return void
     * @see \CharlotteDunois\Luna\AudioTrack::AUDIO_END_REASON_CONTINUE
     */
    function end($track, $reason, $mayStartNext);
    
    /**
     * Emitted when an error happens.
     * @param \CharlotteDunois\Luna\AudioTrack|string     $track
     * @param \CharlotteDunois\Luna\RemoteTrackException  $error
     * @return void
     */
    function error($track, $error);
    
    /**
     * Emitted when the track gets stuck.
     * @param \CharlotteDunois\Luna\AudioTrack|string  $track
     * @param int                                      $threshold  In milliseconds.
     * @return void
     */
    function stuck($track, $threshold);
}
