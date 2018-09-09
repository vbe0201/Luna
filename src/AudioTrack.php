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
 *
 * @property string       $track       The base64 encoded string from Lavalink.
 * @property string       $title       The track title.
 * @property string       $author      The track author.
 * @property int          $duration    The duration of the track in milliseconds.
 * @property string       $identifier  The identifier of the track.
 * @property bool         $stream      Whether the track gets streamed.
 * @property string|null  $url         The URL to the track.
 * @property bool         $seekable    Whether the track can be seeked.
 */
class AudioTrack {
    /**
     * Indicates whether a new track should be started on receiving the `trackEnd` event. If this is false, either this event is
     * already triggered because another track started (REPLACED) or because the player is stopped (STOPPED, CLEANUP).
     * @var array
     * @source
     */
    const AUDIO_END_REASON_CONTINUE = array(
        'FINISHED', 'LOAD_FAILED'
    );
    
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
     * Whether the track can be seeked.
     * @var bool
     */
    protected $seekable;
    
    /**
     * Constructor.
     * @param string       $track       The base64 encoded string from Lavalink.
     * @param string       $title       The track title.
     * @param string       $author      The track author.
     * @param int          $duration    The track duration/length in milliseconds.
     * @param string       $identifier  The track identifier.
     * @param bool         $stream      Whether this track gets streamed.
     * @param string|null  $url         The URL to the track (if the track gets streamed).
     * @param bool         $seekable    Whether the track can be seeked.
     */
    function __construct(string $track, string $title, string $author, int $duration, string $identifier, bool $stream, ?string $url, bool $seekable) {
        $this->track = $track;
        $this->title = $title;
        $this->author = $author;
        $this->duration = $duration;
        $this->identifier = $identifier;
        $this->stream = $stream;
        $this->url = $url;
        $this->seekable = $seekable;
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
     * Creates an Audio Track instance from an array.
     * @param array  $track
     * @return \CharlotteDunois\Luna\AudioTrack
     */
    static function create(array $track) {
        return (new static(
            $track['track'], ($track['info']['title'] ?? ''), ($track['info']['author'] ?? ''), ($track['info']['length'] ?? 0),
            ($track['info']['identifier'] ?? ''), ($track['info']['isStream'] ?? false), ($track['info']['uri'] ?? null), ($track['info']['isSeekable'] ?? false)
        ));
    }
}
