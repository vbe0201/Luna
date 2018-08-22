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
 * Represents an Audio Track.
 */
class AudioTrack {
    /**
     * The base64 encoded string from Lavalink.
     * @var string
     */
    protected $track;
    
    /**
     * The track title.
     * @var string
     */
    protected $title;
    
    /**
     * The track author.
     * @var string
     */
    protected $author;
    
    /**
     * The track duration/length in milliseconds.
     * @var int
     */
    protected $duration;
    
    /**
     * The track identifier.
     * @var string
     */
    protected $identifier;
    
    /**
     * Whether this track gets streamed.
     * @var bool
     */
    protected $stream;
    
    /**
     * The URL to the track (if the track gets streamed).
     * @var string|null
     */
    protected $url;
    
    /**
     * Constructor.
     * @param string       $track       The base64 encoded string from Lavalink.
     * @param string       $title       The track title.
     * @param string       $author      The track author.
     * @param int          $duration    The track duration/length in milliseconds.
     * @param string       $identifier  The track identifier.
     * @param bool         $stream      Whether this track gets streamed.
     * @param string|null  $url         The URL to the track (if the track gets streamed).
     */
    function __construct(string $track, string $title, string $author, int $duration, string $identifier, bool $stream, ?string $url) {
        $this->track = $track;
        $this->title = $title;
        $this->author = $author;
        $this->duration = $duration;
        $this->identifier = $identifier;
        $this->stream = $stream;
        $this->url = $url;
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
