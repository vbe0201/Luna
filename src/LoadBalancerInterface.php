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
 * The load balancer chooses the ideal node based on the load balancer's algorithm.
 */
interface LoadBalancerInterface {
    /**
     * Sets the client. Invoked by `Client::setLoadBalancer`.
     * @param \CharlotteDunois\Luna\Client  $client
     * @return void
     */
    function setClient(\CharlotteDunois\Luna\Client $client): void;
    
    /**
     * Get an ideal node for the region.
     * @param string  $region
     * @param bool    $autoConnect  Automatically make the node connect if it is disconnected (idling).
     * @return \CharlotteDunois\Luna\Link
     * @throws \UnderflowException  Thrown when no nodes are available.
     */
    function getIdealNode(string $region, bool $autoConnect = true): \CharlotteDunois\Luna\Link;
}
