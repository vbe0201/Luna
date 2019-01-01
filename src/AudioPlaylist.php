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
 * Represents an Audio Playlist.
 *
 * @property string|null                          $name           The playlist's name, or null for Lavalink v2.
 * @property int|null                             $selectedTrack  Which track is selected, or null for Lavalink v2.
 * @property \CharlotteDunois\Collect\Collection  $tracks         The playlist's tracks.
 */
class AudioPlaylist {
    /**
     * The playlist's name, or null for Lavalink v2.
     * @var string
     */
    protected $name;
    
    /**
     * Which track is selected, or null for Lavalink v2.
     * @var int
     */
    protected $selectedTrack;
    
    /**
     * The playlist's tracks.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $tracks;
    
    /**
     * Constructor.
     * @param string|null  $name           The playlist's name.
     * @param int|null     $selectedTrack  Which track is selected.
     * @param array[]      $tracks         The track infos.
     */
    function __construct(?string $name, ?int $selectedTrack, array $tracks) {
        $this->name = $name;
        $this->selectedTrack = $selectedTrack;
        $this->tracks = new \CharlotteDunois\Collect\Collection();
        
        foreach($tracks as $track) {
            $audioTrack = \CharlotteDunois\Luna\AudioTrack::create($track);
            $this->tracks->set($audioTrack->identifier, $audioTrack);
        }
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
}
