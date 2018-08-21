# Luna [![Build Status](https://scrutinizer-ci.com/g/CharlotteDunois/Luna/badges/build.png?b=master)](https://scrutinizer-ci.com/g/CharlotteDunois/Luna/build-status/master)

Luna is a Lavalink client for PHP.

This library is **only** for PHP 7.1 (and later).

# Getting Started
Getting started with Luna is pretty straight forward. All you need to do is to use [composer](https://packagist.org/packages/charlottedunois/luna) to install Luna and its dependencies.

```
composer require charlottedunois/luna
```

<br>

**Important Information**: All properties on class instances, which are implemented using a magic method (which means pretty much all properties), are **throwing** if the property doesn't exist.

# Example
This is a fairly trivial example of using Luna. You should put all your listener code into try-catch blocks and handle exceptions accordingly.

```php
// Include composer autoloader

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Luna\Client($loop);

// WIP

$loop->run();
```

# Documentation
None yet.
