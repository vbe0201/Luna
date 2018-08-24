# Luna [![Build Status](https://scrutinizer-ci.com/g/CharlotteDunois/Luna/badges/build.png?b=master)](https://scrutinizer-ci.com/g/CharlotteDunois/Luna/build-status/master)

Luna is a Lavalink client for PHP. For use with [Yasmin](https://github.com/CharlotteDunois/Yasmin), there is a `YasminClient` included, which does the heavy liftings.

This library is **only** for PHP 7.1 (and later).

# Getting Started
Getting started with Luna is pretty straight forward. All you need to do is to use [composer](https://packagist.org/packages/charlottedunois/luna) to install Luna and its dependencies.

```
composer require charlottedunois/luna
```

<br>

**Important Information**: All properties on class instances, which are implemented using a magic method (which means pretty much all properties), are **throwing** if the property doesn't exist.

# Example - Part One
This is a fairly trivial example of using Luna. You should put all your listener code into try-catch blocks and handle exceptions accordingly.

```php
// Include composer autoloader

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Luna\Client($loop, YOUR_USER_ID);

$node = new \CharlotteDunois\Luna\Node('vps-eu', 'password', 'http://http-api-url', 'ws://ws-api-url', 'eu');
$client->addNode($node);

$client->start()->done();

$loop->run();
```

# Example - Part Two
When you have sent a voice state update event and Discord responded with the two events, you have to provide the VOICE_SERVER_UPDATE event as-is, unmodified.

```php
$player = $node->sendVoiceUpdate($guildID, $sessionID, $voiceServerUpdateEvent);

$node->resolveTrack('DT61L8hbbJ4')->done(function ($audioTrack) use ($player) {
    $player->play($audioTrack);
});
```

# Yasmin Example - Part One

```php
// Include composer autoloader

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Yasmin\Client(array(), $loop);
$luna = new \CharlotteDunois\Luna\YasminClient($client);

$client->once('ready', function () use ($luna) {
    $luna->start()->done();
});

$node = new \CharlotteDunois\Luna\Node('vps-eu', 'password', 'http://http-api-url', 'ws://ws-api-url', 'eu');
$luna->addNode($node);

$client->login('YOUR_TOKEN');
$loop->run();
```

# Yasmin Example - Part Two
The `YasminClient` has a method called `joinChannel` which sends the voice state update to discord, waits for the two events and sends them to the lavalink node.

```php
$luna->joinChannel($voiceChannel)->done(function ($player) {
    // The code
});
```

# Documentation
https://luna.neko.run/
