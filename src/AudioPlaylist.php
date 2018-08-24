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
 * Represents an Audio Playlist.
 * @property string                               $name           The playlist's name.
 * @property int                                  $selectedTrack  Which track is selected.
 * @property \CharlotteDunois\Collect\Collection  $tracks         The playlist's tracks.
 */
class AudioPlaylist {
    /**
     * The playlist's name.
     * @var string
     */
    protected $name;
    
    /**
     * Which track is selected.
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
     * @param string   $name           The playlist's name.
     * @param int      $selectedTrack  Which track is selected.
     * @param array[]  $tracks         The track infos.
     */
    function __construct(string $name, int $selectedTrack, array $tracks) {
        $this->name = $name;
        $this->selectedTrack = $selectedTrack;
        $this->tracks = new \CharlotteDunois\Collect\Collection();
        
        foreach($tracks as $track) {
            $audioTrack = \CharlotteDunois\Luna\AudioTrack::create($track);
            $this->tracks->set($audioTrack->identifier, $audioTrack);
        }
    }
    
    /**
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
