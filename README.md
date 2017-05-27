# Cache Engine
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/cache-engine-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/cache-engine-php/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f643fd22-8ab1-4f41-9bef-f9f9e127ec0d/mini.png)](https://insight.sensiolabs.com/projects/f643fd22-8ab1-4f41-9bef-f9f9e127ec0d)
[![Build Status](https://travis-ci.org/byjg/cache-engine-php.svg?branch=master)](https://travis-ci.org/byjg/cache-engine-php)


## Description

A multi-purpose cache engine PSR-6 and PSR-16 implementation with several drivers.

## Cache Engine PSR-16 compliant
 
PSR-16 defines a Simple Cache interface with less verbosity than PSR-6. Below a list
of engines available in this library that is PSR-16 compliant:

| Class                             | Description                                                         |
|:----------------------------------|:--------------------------------------------------------------------|
| \ByJG\Cache\NoCacheEngine         | Do nothing. Use it for disable the cache without change your code   |
| \ByJG\Cache\ArrayCacheEngine      | Local cache only using array. It does not persists between requests |
| \ByJG\Cache\FileSystemCacheEngine | Save the cache result in the local file system                      |
| \ByJG\Cache\MemcachedEngine       | Uses the Memcached as the cache engine                              |
| \ByJG\Cache\SessionCachedEngine   | uses the PHP session as cache                                       |
| \ByJG\Cache\ShmopCachedEngine     | uses the shared memory area for cache                               |

To create a new Cache Instance just create the proper cache engine and use it:

```php
<?php
$cache = new \ByJG\Cache\Engine\FileSystemCacheEngine();

// And use it:
$object = $cache->get('key');
$cache->set('key', 'value');
if ($cache->has('key')) {
    //...
};
```

## Cache Engine PSR-6 compliant 

The PSR-6 implementation use the engines defined above. PSR-6 is more verbosity and
have an extra layer do get and set the cache values. 

You can use one of the factory methods to create a instance of the CachePool implementation:

```php
<?php
$cachePool = \ByJG\Cache\Factory::createFilePool();
```

 OR just create a new CachePool and pass to the constructor an instance of a PSR-16 compliant class:

```php
$cachePool = new CachePool(new FileSystemCacheEngine());
```

## List of Avaiable Factory Commands

**Note: All parameters are optional**

| Engine           | Factory Command                                                       |
|:-----------------|:----------------------------------------------------------------------|
| No Cache         | Factory::createNullPool($prefix, $bufferSize, $logger);               |
| Array            | Factory::createArrayPool($bufferSize, $logger);                       |
| File System      | Factory::createFilePool($prefix, $bufferSize, $logger);               |
| Memcached        | Factory::createMemcachedPool($servers[], $bufferSize, $logger);       |
| Session          | Factory::createSessionPool($prefix, $bufferSize, $logger);            |
| Redis            | Factory::createRedisCacheEngine($server, $pwd, $bufferSize, $logger); |
| Shmop            | Factory::createShmopPool($config[], $bufferSize, $logger);            |

The Commom parameters are:

- logger: A valid instance that implement the LoggerInterface defined by the PSR/LOG
- bufferSize: the Buffer of CachePool
- prefix: A prefix name to compose the KEY physically 
- servers: An array of memcached servers. E.g.: `[ '127.0.0.1:11211' ]` 
- config: Specific setup for shmop. E.g.: `[ 'max-size' => 524288, 'default-permission' => '0700' ]`

## Logging cache commands
 
You can add a PSR Log compatible to the constructor in order to get Log of the operations

## Install

Just type: `composer require "byjg/cache-engine=4.0.*"`


## Running Unit Testes

```
phpunit --stderr
```

**Note:** the parameter `--stderr` after `phpunit` is to permit run the tests on SessionCacheEngine.  

----
[Open source ByJG](http://opensource.byjg.com)
