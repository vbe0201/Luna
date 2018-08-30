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
 * A load balacer chooses the node based on the node's stats.
 * @property \CharlotteDunois\Luna\Client  $client  The Luna client.
 */
class LoadBalancer {
    /**
     *  The Luna client.
     * @var \CharlotteDunois\Luna\Client
     */
    protected $client;
    
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
     * Sets the client. Invoked by `Client::setLoadBalancer`.
     * @param \CharlotteDunois\Luna\Client  $client
     * @return void
     */
    function setClient(\CharlotteDunois\Luna\Client $client) {
        $this->client = $client;
    }
    
    /**
     * Get an ideal node for the region. If there is no ideal node, this will return the first node in the list.
     * @param string  $region
     * @param bool    $autoConnect  Automatically make the node connect if it is disconnected (idling).
     * @return \CharlotteDunois\Luna\Link
     * @throws \UnderflowException  Thrown when no nodes are available
     */
    function getIdealNode(string $region, bool $autoConnect = true) {
        if($this->client->links->count() === 0) {
            throw new \UnderflowException('No nodes added');
        }
        
        $linkStats = $this->calculateStats($this->client->links);
        $link = $this->selectNode($linkStats, $region);
        
        if(!$link) {
            $link = $this->client->links->first(function (\CharlotteDunois\Luna\Link $link) {
                return ($link->status >= \CharlotteDunois\Luna\Link::STATUS_CONNECTED);
            });
            
            if(!$link) {
                throw new \UnderflowException('No node available');
            }
        }
        
        if($autoConnect && $link->status === \CharlotteDunois\Luna\Link::STATUS_IDLE) {
            $link->connect();
        }
        
        return $link;
    }
    
    /**
     * Calculates each node's stats.
     * @param \CharlotteDunois\Collect\Collection  $links
     * @return array
     */
    protected function calculateStats(\CharlotteDunois\Collect\Collection $links) {
        $linkStats = array();
        
        foreach($links as $link) {
            if(!isset($linkStats[$link->region])) {
                $linkStats[$link->node->region] = array();
            }
            
            if($link->status < \CharlotteDunois\Luna\Link::STATUS_CONNECTED) {
                continue;
            }
            
            if(!$link->stats) {
                $linkStats[$link->node->region][] = array('node' => $link, 'penalty' => \INF);
                continue;
            }
            
            $playerPenalty = $link->stats->playingPlayers;
            $cpuPenalty = (int) ((\pow(1.05, (100 * $link->stats->systemLoad)) * 10) - 10);
            
            if($link->stats->framesDeficit !== null) {
                $deficitPenalty = (int) ((\pow(1.03, (500 * ($link->stats->framesDeficit / 3000))) * 600) - 600);
                $nullPenalty = (int) (((\pow(1.03, (500 * ($link->stats->framesNulled / 3000))) * 300) - 6300) * 2);
            } else {
                $deficitPenalty = 0;
                $nullPenalty = 0;
            }
            
            $total = $playerPenalty + $cpuPenalty + $deficitPenalty + $nullPenalty;
            $linkStats[$link->node->region][] = array('node' => $link, 'penalty' => $total);
        }
        
        return $linkStats;
    }
    
    /**
     * Selects a node based on the stats.
     * @param array   $linkStats
     * @param string  $region
     * @return \CharlotteDunois\Luna\Link|null
     */
    protected function selectNode(array $linkStats, string $region) {
        $link = null;
        $low = null;
        
        if(!empty($linkStats[$region])) {
            foreach($linkStats[$region] as $stat) {
                if($low === null || $stat['penalty'] < $low['penalty']) {
                    $low = $stat;
                }
            }
        } else {
            foreach($linkStats as $region) {
                foreach($region as $stat) {
                    if($low === null || $stat['penalty'] < $low['penalty']) {
                        $low = $stat;
                    }
                }
            }
        }
        
        if($low !== null) {
            $link = $low['node'];
        }
        
        return $link;
    }
}
