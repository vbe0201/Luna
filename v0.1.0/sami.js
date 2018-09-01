
window.projectVersion = 'v0.1.0';

(function(root) {

    var bhIndex = null;
    var rootPath = '';
    var treeHtml = '        <ul>                <li data-name="namespace:CharlotteDunois" class="opened">                    <div style="padding-left:0px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="CharlotteDunois.html">CharlotteDunois</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="namespace:CharlotteDunois_Luna" class="opened">                    <div style="padding-left:18px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="CharlotteDunois/Luna.html">Luna</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="class:CharlotteDunois_Luna_AudioPlaylist" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/AudioPlaylist.html">AudioPlaylist</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_AudioTrack" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/AudioTrack.html">AudioTrack</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_Client" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/Client.html">Client</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_ClientEvents" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/ClientEvents.html">ClientEvents</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_Link" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/Link.html">Link</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_LoadBalancer" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/LoadBalancer.html">LoadBalancer</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_LoadBalancerInterface" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/LoadBalancerInterface.html">LoadBalancerInterface</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_Node" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/Node.html">Node</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_Player" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/Player.html">Player</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_PlayerEvents" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/PlayerEvents.html">PlayerEvents</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_RemoteStats" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/RemoteStats.html">RemoteStats</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_RemoteTrackException" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/RemoteTrackException.html">RemoteTrackException</a>                    </div>                </li>                            <li data-name="class:CharlotteDunois_Luna_YasminClient" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="CharlotteDunois/Luna/YasminClient.html">YasminClient</a>                    </div>                </li>                </ul></div>                </li>                </ul></div>                </li>                </ul>';

    var searchTypeClasses = {
        'Namespace': 'label-default',
        'Class': 'label-info',
        'Interface': 'label-primary',
        'Trait': 'label-success',
        'Method': 'label-danger',
        '_': 'label-warning'
    };

    var searchIndex = [
                    
            {"type": "Namespace", "link": "CharlotteDunois.html", "name": "CharlotteDunois", "doc": "Namespace CharlotteDunois"},{"type": "Namespace", "link": "CharlotteDunois/Luna.html", "name": "CharlotteDunois\\Luna", "doc": "Namespace CharlotteDunois\\Luna"},
            {"type": "Interface", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/ClientEvents.html", "name": "CharlotteDunois\\Luna\\ClientEvents", "doc": "&quot;This interface documents all events emitted on the client. Events emitted on the links are re-emitted on the client, with the additional argument &lt;code&gt;$link&lt;\/code&gt;, (as such documented here).&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\ClientEvents", "fromLink": "CharlotteDunois/Luna/ClientEvents.html", "link": "CharlotteDunois/Luna/ClientEvents.html#method_debug", "name": "CharlotteDunois\\Luna\\ClientEvents::debug", "doc": "&quot;Debug messages.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\ClientEvents", "fromLink": "CharlotteDunois/Luna/ClientEvents.html", "link": "CharlotteDunois/Luna/ClientEvents.html#method_error", "name": "CharlotteDunois\\Luna\\ClientEvents::error", "doc": "&quot;Emitted when an error happens. You should always listen on this event.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\ClientEvents", "fromLink": "CharlotteDunois/Luna/ClientEvents.html", "link": "CharlotteDunois/Luna/ClientEvents.html#method_disconnect", "name": "CharlotteDunois\\Luna\\ClientEvents::disconnect", "doc": "&quot;Emitted when the node gets disconnected.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\ClientEvents", "fromLink": "CharlotteDunois/Luna/ClientEvents.html", "link": "CharlotteDunois/Luna/ClientEvents.html#method_failover", "name": "CharlotteDunois\\Luna\\ClientEvents::failover", "doc": "&quot;Emitted when a failover happens. Only emitted on the client.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\ClientEvents", "fromLink": "CharlotteDunois/Luna/ClientEvents.html", "link": "CharlotteDunois/Luna/ClientEvents.html#method_newPlayer", "name": "CharlotteDunois\\Luna\\ClientEvents::newPlayer", "doc": "&quot;Emitted when a new player gets created.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\ClientEvents", "fromLink": "CharlotteDunois/Luna/ClientEvents.html", "link": "CharlotteDunois/Luna/ClientEvents.html#method_stats", "name": "CharlotteDunois\\Luna\\ClientEvents::stats", "doc": "&quot;Emitted when the node gets stats from the lavalink node.&quot;"},
            
            {"type": "Interface", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/LoadBalancerInterface.html", "name": "CharlotteDunois\\Luna\\LoadBalancerInterface", "doc": "&quot;The load balancer chooses the ideal node based on the load balancer&#039;s algorithm.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\LoadBalancerInterface", "fromLink": "CharlotteDunois/Luna/LoadBalancerInterface.html", "link": "CharlotteDunois/Luna/LoadBalancerInterface.html#method_setClient", "name": "CharlotteDunois\\Luna\\LoadBalancerInterface::setClient", "doc": "&quot;Sets the client. Invoked by &lt;code&gt;Client::setLoadBalancer&lt;\/code&gt;.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\LoadBalancerInterface", "fromLink": "CharlotteDunois/Luna/LoadBalancerInterface.html", "link": "CharlotteDunois/Luna/LoadBalancerInterface.html#method_getIdealNode", "name": "CharlotteDunois\\Luna\\LoadBalancerInterface::getIdealNode", "doc": "&quot;Get an ideal node for the region.&quot;"},
            
            {"type": "Interface", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/PlayerEvents.html", "name": "CharlotteDunois\\Luna\\PlayerEvents", "doc": "&quot;This interface documents all events emitted on the player.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\PlayerEvents", "fromLink": "CharlotteDunois/Luna/PlayerEvents.html", "link": "CharlotteDunois/Luna/PlayerEvents.html#method_end", "name": "CharlotteDunois\\Luna\\PlayerEvents::end", "doc": "&quot;Emitted when the track ends.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\PlayerEvents", "fromLink": "CharlotteDunois/Luna/PlayerEvents.html", "link": "CharlotteDunois/Luna/PlayerEvents.html#method_error", "name": "CharlotteDunois\\Luna\\PlayerEvents::error", "doc": "&quot;Emitted when an error happens.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\PlayerEvents", "fromLink": "CharlotteDunois/Luna/PlayerEvents.html", "link": "CharlotteDunois/Luna/PlayerEvents.html#method_stuck", "name": "CharlotteDunois\\Luna\\PlayerEvents::stuck", "doc": "&quot;Emitted when the track gets stuck.&quot;"},
            
            
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/AudioPlaylist.html", "name": "CharlotteDunois\\Luna\\AudioPlaylist", "doc": "&quot;Represents an Audio Playlist.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\AudioPlaylist", "fromLink": "CharlotteDunois/Luna/AudioPlaylist.html", "link": "CharlotteDunois/Luna/AudioPlaylist.html#method___construct", "name": "CharlotteDunois\\Luna\\AudioPlaylist::__construct", "doc": "&quot;Constructor.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/AudioTrack.html", "name": "CharlotteDunois\\Luna\\AudioTrack", "doc": "&quot;Represents an Audio Track.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\AudioTrack", "fromLink": "CharlotteDunois/Luna/AudioTrack.html", "link": "CharlotteDunois/Luna/AudioTrack.html#method___construct", "name": "CharlotteDunois\\Luna\\AudioTrack::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\AudioTrack", "fromLink": "CharlotteDunois/Luna/AudioTrack.html", "link": "CharlotteDunois/Luna/AudioTrack.html#method_create", "name": "CharlotteDunois\\Luna\\AudioTrack::create", "doc": "&quot;Creates an Audio Track instance from an array.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/Client.html", "name": "CharlotteDunois\\Luna\\Client", "doc": "&quot;The generic Lavalink client. It does absolutely nothing for you on the Discord side.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method___construct", "name": "CharlotteDunois\\Luna\\Client::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_getOption", "name": "CharlotteDunois\\Luna\\Client::getOption", "doc": "&quot;Get a specific option, or the default value.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_setLoadBalancer", "name": "CharlotteDunois\\Luna\\Client::setLoadBalancer", "doc": "&quot;Sets a loadbalancer to use.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_addNode", "name": "CharlotteDunois\\Luna\\Client::addNode", "doc": "&quot;Adds a node.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_removeNode", "name": "CharlotteDunois\\Luna\\Client::removeNode", "doc": "&quot;Removes a node.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_getIdealNode", "name": "CharlotteDunois\\Luna\\Client::getIdealNode", "doc": "&quot;Get an ideal node for the region solely based on region. If there is no ideal node, this will return the first connected node in the list.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_start", "name": "CharlotteDunois\\Luna\\Client::start", "doc": "&quot;Starts all connections to the links.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_stop", "name": "CharlotteDunois\\Luna\\Client::stop", "doc": "&quot;Stops all connections to the links.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_createNodes", "name": "CharlotteDunois\\Luna\\Client::createNodes", "doc": "&quot;Creates nodes as part of a factory and adds them to the client. This is useful to import node configurations from a file.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Client", "fromLink": "CharlotteDunois/Luna/Client.html", "link": "CharlotteDunois/Luna/Client.html#method_createHTTPRequest", "name": "CharlotteDunois\\Luna\\Client::createHTTPRequest", "doc": "&quot;Executes an asychronous HTTP request. Used by &lt;code&gt;Link::resolveTrack&lt;\/code&gt;. Resolves with an instance of ResponseInterface.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/Link.html", "name": "CharlotteDunois\\Luna\\Link", "doc": "&quot;A link connects to the lavalink node and listens for events and sends packets.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Link", "fromLink": "CharlotteDunois/Luna/Link.html", "link": "CharlotteDunois/Luna/Link.html#method___construct", "name": "CharlotteDunois\\Luna\\Link::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Link", "fromLink": "CharlotteDunois/Luna/Link.html", "link": "CharlotteDunois/Luna/Link.html#method_connect", "name": "CharlotteDunois\\Luna\\Link::connect", "doc": "&quot;Connects to the node websocket.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Link", "fromLink": "CharlotteDunois/Luna/Link.html", "link": "CharlotteDunois/Luna/Link.html#method_disconnect", "name": "CharlotteDunois\\Luna\\Link::disconnect", "doc": "&quot;Closes the connection to the node websocket.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Link", "fromLink": "CharlotteDunois/Luna/Link.html", "link": "CharlotteDunois/Luna/Link.html#method_send", "name": "CharlotteDunois\\Luna\\Link::send", "doc": "&quot;Sends a packet.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Link", "fromLink": "CharlotteDunois/Luna/Link.html", "link": "CharlotteDunois/Luna/Link.html#method_createPlayer", "name": "CharlotteDunois\\Luna\\Link::createPlayer", "doc": "&quot;Send a voice update event to the node, creates a new player and adds it to the collection.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Link", "fromLink": "CharlotteDunois/Luna/Link.html", "link": "CharlotteDunois/Luna/Link.html#method_resolveTrack", "name": "CharlotteDunois\\Luna\\Link::resolveTrack", "doc": "&quot;Resolves a track using Lavalink&#039;s REST API. Resolves with an instance of AudioTrack, an instance of AudioPlaylist or a Collection of AudioTrack instances (for search results), mapped by the track identifier.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/LoadBalancer.html", "name": "CharlotteDunois\\Luna\\LoadBalancer", "doc": "&quot;A load balacer chooses the node based on the node&#039;s stats.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\LoadBalancer", "fromLink": "CharlotteDunois/Luna/LoadBalancer.html", "link": "CharlotteDunois/Luna/LoadBalancer.html#method_setClient", "name": "CharlotteDunois\\Luna\\LoadBalancer::setClient", "doc": "&quot;Sets the client. Invoked by &lt;code&gt;Client::setLoadBalancer&lt;\/code&gt;.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\LoadBalancer", "fromLink": "CharlotteDunois/Luna/LoadBalancer.html", "link": "CharlotteDunois/Luna/LoadBalancer.html#method_getIdealNode", "name": "CharlotteDunois\\Luna\\LoadBalancer::getIdealNode", "doc": "&quot;Get an ideal node for the region. If there is no ideal node, this will return the first node in the list.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/Node.html", "name": "CharlotteDunois\\Luna\\Node", "doc": "&quot;This class represents a node.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Node", "fromLink": "CharlotteDunois/Luna/Node.html", "link": "CharlotteDunois/Luna/Node.html#method___construct", "name": "CharlotteDunois\\Luna\\Node::__construct", "doc": "&quot;Constructor.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/Player.html", "name": "CharlotteDunois\\Luna\\Player", "doc": "&quot;Represents a player of a guild on a node.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method___construct", "name": "CharlotteDunois\\Luna\\Player::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_play", "name": "CharlotteDunois\\Luna\\Player::play", "doc": "&quot;Plays a track.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_stop", "name": "CharlotteDunois\\Luna\\Player::stop", "doc": "&quot;Stops playing a track.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_destroy", "name": "CharlotteDunois\\Luna\\Player::destroy", "doc": "&quot;Destroys the player.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_getLastPosition", "name": "CharlotteDunois\\Luna\\Player::getLastPosition", "doc": "&quot;Gets the last position of the played track in milliseconds.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_seekTo", "name": "CharlotteDunois\\Luna\\Player::seekTo", "doc": "&quot;Seeks the track.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_setPaused", "name": "CharlotteDunois\\Luna\\Player::setPaused", "doc": "&quot;Sets the paused state of the track.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_setVolume", "name": "CharlotteDunois\\Luna\\Player::setVolume", "doc": "&quot;Sets the volume of the player.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_sendVoiceUpdate", "name": "CharlotteDunois\\Luna\\Player::sendVoiceUpdate", "doc": "&quot;Send a voice update event for the player.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_setNode", "name": "CharlotteDunois\\Luna\\Player::setNode", "doc": "&quot;Sets the node. Used for the internal failover.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\Player", "fromLink": "CharlotteDunois/Luna/Player.html", "link": "CharlotteDunois/Luna/Player.html#method_updateState", "name": "CharlotteDunois\\Luna\\Player::updateState", "doc": "&quot;Updates the player state. Invoked by Lavalink.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/RemoteStats.html", "name": "CharlotteDunois\\Luna\\RemoteStats", "doc": "&quot;Represents a node&#039;s stats. The lavalink node sends every minute stats, which updates any existing instances.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\RemoteStats", "fromLink": "CharlotteDunois/Luna/RemoteStats.html", "link": "CharlotteDunois/Luna/RemoteStats.html#method___construct", "name": "CharlotteDunois\\Luna\\RemoteStats::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\RemoteStats", "fromLink": "CharlotteDunois/Luna/RemoteStats.html", "link": "CharlotteDunois/Luna/RemoteStats.html#method_update", "name": "CharlotteDunois\\Luna\\RemoteStats::update", "doc": "&quot;Updates the stats.&quot;"},
            {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/RemoteTrackException.html", "name": "CharlotteDunois\\Luna\\RemoteTrackException", "doc": "&quot;Represents a remote track exception.&quot;"},
                    {"type": "Class", "fromName": "CharlotteDunois\\Luna", "fromLink": "CharlotteDunois/Luna.html", "link": "CharlotteDunois/Luna/YasminClient.html", "name": "CharlotteDunois\\Luna\\YasminClient", "doc": "&quot;The Lavalink client for Yasmin. This class interacts with Yasmin to do all the updates for you.&quot;"},
                                                        {"type": "Method", "fromName": "CharlotteDunois\\Luna\\YasminClient", "fromLink": "CharlotteDunois/Luna/YasminClient.html", "link": "CharlotteDunois/Luna/YasminClient.html#method___construct", "name": "CharlotteDunois\\Luna\\YasminClient::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\YasminClient", "fromLink": "CharlotteDunois/Luna/YasminClient.html", "link": "CharlotteDunois/Luna/YasminClient.html#method_destroy", "name": "CharlotteDunois\\Luna\\YasminClient::destroy", "doc": "&quot;Removes all listeners from Yasmin.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\YasminClient", "fromLink": "CharlotteDunois/Luna/YasminClient.html", "link": "CharlotteDunois/Luna/YasminClient.html#method_start", "name": "CharlotteDunois\\Luna\\YasminClient::start", "doc": "&quot;Starts all connections to the nodes. Can only be called &lt;strong&gt;after&lt;\/strong&gt; Yasmin turned ready.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\YasminClient", "fromLink": "CharlotteDunois/Luna/YasminClient.html", "link": "CharlotteDunois/Luna/YasminClient.html#method_joinChannel", "name": "CharlotteDunois\\Luna\\YasminClient::joinChannel", "doc": "&quot;Joins a voice channel. The guild region will be stripped down to &lt;code&gt;eu&lt;\/code&gt;, &lt;code&gt;us&lt;\/code&gt;, etc. Resolves with an instance of Player.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\YasminClient", "fromLink": "CharlotteDunois/Luna/YasminClient.html", "link": "CharlotteDunois/Luna/YasminClient.html#method_leaveChannel", "name": "CharlotteDunois\\Luna\\YasminClient::leaveChannel", "doc": "&quot;Leaves a voice channel and destroys any existing player.&quot;"},
                    {"type": "Method", "fromName": "CharlotteDunois\\Luna\\YasminClient", "fromLink": "CharlotteDunois/Luna/YasminClient.html", "link": "CharlotteDunois/Luna/YasminClient.html#method_moveToChannel", "name": "CharlotteDunois\\Luna\\YasminClient::moveToChannel", "doc": "&quot;Moves to a different voice channel in the same guild.&quot;"},
            
                                        // Fix trailing commas in the index
        {}
    ];

    /** Tokenizes strings by namespaces and functions */
    function tokenizer(term) {
        if (!term) {
            return [];
        }

        var tokens = [term];
        var meth = term.indexOf('::');

        // Split tokens into methods if "::" is found.
        if (meth > -1) {
            tokens.push(term.substr(meth + 2));
            term = term.substr(0, meth - 2);
        }

        // Split by namespace or fake namespace.
        if (term.indexOf('\\') > -1) {
            tokens = tokens.concat(term.split('\\'));
        } else if (term.indexOf('_') > 0) {
            tokens = tokens.concat(term.split('_'));
        }

        // Merge in splitting the string by case and return
        tokens = tokens.concat(term.match(/(([A-Z]?[^A-Z]*)|([a-z]?[^a-z]*))/g).slice(0,-1));

        return tokens;
    };

    root.Sami = {
        /**
         * Cleans the provided term. If no term is provided, then one is
         * grabbed from the query string "search" parameter.
         */
        cleanSearchTerm: function(term) {
            // Grab from the query string
            if (typeof term === 'undefined') {
                var name = 'search';
                var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
                var results = regex.exec(location.search);
                if (results === null) {
                    return null;
                }
                term = decodeURIComponent(results[1].replace(/\+/g, " "));
            }

            return term.replace(/<(?:.|\n)*?>/gm, '');
        },

        /** Searches through the index for a given term */
        search: function(term) {
            // Create a new search index if needed
            if (!bhIndex) {
                bhIndex = new Bloodhound({
                    limit: 500,
                    local: searchIndex,
                    datumTokenizer: function (d) {
                        return tokenizer(d.name);
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace
                });
                bhIndex.initialize();
            }

            results = [];
            bhIndex.get(term, function(matches) {
                results = matches;
            });

            if (!rootPath) {
                return results;
            }

            // Fix the element links based on the current page depth.
            return $.map(results, function(ele) {
                if (ele.link.indexOf('..') > -1) {
                    return ele;
                }
                ele.link = rootPath + ele.link;
                if (ele.fromLink) {
                    ele.fromLink = rootPath + ele.fromLink;
                }
                return ele;
            });
        },

        /** Get a search class for a specific type */
        getSearchClass: function(type) {
            return searchTypeClasses[type] || searchTypeClasses['_'];
        },

        /** Add the left-nav tree to the site */
        injectApiTree: function(ele) {
            ele.html(treeHtml);
        }
    };

    $(function() {
        // Modify the HTML to work correctly based on the current depth
        rootPath = $('body').attr('data-root-path');
        treeHtml = treeHtml.replace(/href="/g, 'href="' + rootPath);
        Sami.injectApiTree($('#api-tree'));
    });

    return root.Sami;
})(window);

$(function() {

    // Enable the version switcher
    $('#version-switcher').change(function() {
        window.location = $(this).val()
    });

    
        // Toggle left-nav divs on click
        $('#api-tree .hd span').click(function() {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }

    
    
        var form = $('#search-form .typeahead');
        form.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            name: 'search',
            displayKey: 'name',
            source: function (q, cb) {
                cb(Sami.search(q));
            }
        });

        // The selection is direct-linked when the user selects a suggestion.
        form.on('typeahead:selected', function(e, suggestion) {
            window.location = suggestion.link;
        });

        // The form is submitted when the user hits enter.
        form.keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').submit();
                return true;
            }
        });

    
});


