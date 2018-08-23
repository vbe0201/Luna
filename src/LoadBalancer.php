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
 * A load balacer chooses the node based on the node's health.
 */
class LoadBalancer {
    /**
     *  The Luna client.
     * @var \CharlotteDunois\Luna\Client
     */
    protected $client;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Luna\Client  $client
     */
    function __construct(\CharlotteDunois\Luna\Client $client) {
        $this->client = $client;
    }
    
    /**
     * Get an ideal node for the region. If there is no ideal node, this will return the first node in the list.
     * @param string  $region
     * @param bool    $autoConnect  Automatically make the node connect if it is disconnected (idling).
     * @return \CharlotteDunois\Luna\Node
     * @throws \UnderflowException
     */
    function getIdealNode(string $region, bool $autoConnect = true) {
        if($this->client->nodes->count() === 0) {
            throw new \UnderflowException('No nodes added');
        }
        
        $nodeStats = array();
        
        foreach($this->client->nodes as $node) {
            if(!isset($nodeStats[$node->region])) {
                $nodeStats[$node->region] = array();
            }
            
            if(!$node->stats) {
                $nodeStats[$node->region][] = array('node' => $node, 'penalty' => \INF);
                continue;
            }
            
            $playerPenalty = $node->stats->playingPlayers;
            $cpuPenalty = (int) ((\pow(1.05, (100 * $node->stats->systemLoad)) * 10) - 10);
            
            if($node->stats->framesDeficit !== null) {
                $deficitPenalty = (int) ((\pow(1.03, (500 * ($node->stats->framesDeficit / 3000))) * 600) - 600);
                $nullPenalty = (int) (((\pow(1.03, (500 * ($node->stats->framesNulled / 3000))) * 300) - 6300) * 2);
            } else {
                $deficitPenalty = 0;
                $nullPenalty = 0;
            }
            
            $total = $playerPenalty + $cpuPenalty + $deficitPenalty + $nullPenalty;
            $nodeStats[$node->region][] = array('node' => $node, 'penalty' => $total);
        }
        
        $node = null;
        $low = null;
        
        if(!empty($nodeStats[$region])) {
            foreach($nodeStats[$region] as $stat) {
                if($low === null || $stat['penalty'] < $low['penalty']) {
                    $low = $stat;
                }
            }
        } else {
            foreach($nodeStats as $region) {
                foreach($region as $stat) {
                    if($low === null || $stat['penalty'] < $low['penalty']) {
                        $low = $stat;
                    }
                }
            }
        }
        
        if($low !== null) {
            $node = $low['node'];
        }
        
        if(!$node) {
            $node = $this->client->nodes->first();
        }
        
        if($autoConnect && $node->link->status === \CharlotteDunois\Luna\Link::STATUS_IDLE) {
            $node->link->connect();
        }
        
        return $node;
    }
}
