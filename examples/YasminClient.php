<?php
/**
 * Luna
 * Copyright 2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Luna/blob/master/LICENSE
*/

/*
 * This example will demonstrate the usage of the YasminClient.
 */

require_once(__DIR__.'/vendor/autoload.php');

function my_log_error(\Throwable $error) {
    // Log the error or something
    echo $error.PHP_EOL;
}

$loop = \React\EventLoop\Factory::create();

$client = new \CharlotteDunois\Yasmin\Client(array(), $loop);
$luna = new \CharlotteDunois\Luna\YasminClient($client);

$client->once('ready', function () use ($luna) {
    $luna->start()->done();
});

$client->on('message', function (\CharlotteDunois\Yasmin\Models\Message $message) use ($client, $luna) {
    if(substr($message->content, 0, 2) !== '~>' || $message->channel->type !== 'text') {
        return;
    }
    
    $args = explode(' ', substr($message->content, 2));
    $command = array_shift($args);
    
    if($command === 'join') {
        // Check whether the user is not in a voice or we already are in one
        if($message->member->voiceChannelID === null) {
            return $message->reply('You need to be in a voice channel!')
                ->done(null, 'my_log_error');
        } elseif($message->guild->me->voiceChannelID !== null) {
            return $message->reply('I am already in a voice channel.')
                ->done(null, 'my_log_error');
        }
        
        $channel = $message->member->voiceChannel;
        $perms = $channel->permissionsFor($channel->guild->me);
        
        // Check whether we can connect
        if(!$perms->has('CONNECT') && !$perms->has('MOVE_MEMBERS')) {
            return $message->reply('Insufficient permissions to join the voice channel')
                ->done(null, 'my_log_error');
        }
        
        // Check whether the user limit has been reached
        if($channel->userLimit > 0 && $channel->members->count() >= $channel->userLimit && !$perms->has('MOVE_MEMBERS')) {
            return $message->reply('Voice channel user limit reached, unable to join the voice channel')
                ->done(null, 'my_log_error');
        }
        
        // Check whether we can speak
        if(!$perms->has('SPEAK')) {
            return $message->reply('We can not speak in the voice channel, joining makes no sense')
                ->done(null, 'my_log_error');
        }
        
        // Join the channel
        $luna->joinChannel($channel)->done(function (\CharlotteDunois\Luna\Player $player) {
            $player->on('error', 'my_log_error');
        }, function ($error) use ($message) {
            my_log_error($error);
            $message->reply('Unable to join channel')->done(null, 'my_log_error');
        });
    } elseif($command === 'play') {
        if($message->guild->me->voiceChannelID === null) {
            return $message->reply('Please use `~>join` first.')
                ->done(null, 'my_log_error');
        }
        
        // Get the guild's player
        $player = $luna->connections->get(((int) $message->guild->id));
        
        // Sanity check
        if($player === null) {
            $luna->leaveChannel($message->guild->me->voiceChannel)
                ->done(null, 'my_log_error');
            
            return $message->reply('There has been an error when getting the player.')
                ->done(null, 'my_log_error');
        }
        
        // Resolve the track and then play it, if possible
        $player->link->resolveTrack(implode(' ', $args))->then(function ($result) use ($message, $player) {
            // If it's a playlist or a search result, we just play the first one
            if($result instanceof \CharlotteDunois\Luna\AudioPlaylist) {
                return $player->play($result->tracks->first());
            } elseif($result instanceof \CharlotteDunois\Collect\Collection) {
                return $player->play($result->first());
            }
            
            // $result is an instance of \CharlotteDunois\Luna\AudioTrack
            return $player->play($result);
        }, function ($error) use ($message) {
            // No matches or loading failed exceptions
            if($error instanceof \RangeException || $error instanceof \UnexpectedValueException) {
                return $message->reply($error->getMessage());
            }
            
            throw $error;
        })->done(null, 'my_log_error');
    } elseif($command === 'leave') {
        // Check whether we are not in a voice channel
        if($message->guild->me->voiceChannelID === null) {
            return $message->reply('I am not in a voice channel.')
                ->done(null, 'my_log_error');
        }
        
        // Leave the channel
        $luna->leaveChannel($message->guild->me->voiceChannel)
            ->done(function () use ($message) {
                $message->reply('We have left the channel.')
                    ->done(null, 'my_log_error');
            }, 'my_log_error');
    }
});

$node = new \CharlotteDunois\Luna\Node('vps-eu', 'password', 'http://http-api-url', 'ws://ws-api-url', 'eu');
$luna->addNode($node);

$client->login('YOUR_TOKEN')->done();
$loop->run();
