# Cache Engine
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/cache-engine-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/cache-engine-php/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f643fd22-8ab1-4f41-9bef-f9f9e127ec0d/mini.png)](https://insight.sensiolabs.com/projects/f643fd22-8ab1-4f41-9bef-f9f9e127ec0d)
[![Build Status](https://travis-ci.org/byjg/cache-engine-php.svg?branch=master)](https://travis-ci.org/byjg/cache-engine-php)


## Description

A multi-purpose cache engine in PHP with several drivers. PSR-6 compliant.

## Avaible cache engines

| Class                             | Description                                                         |
|:----------------------------------|:--------------------------------------------------------------------|
| \ByJG\Cache\NoCacheEngine         | Do nothing. Use it for disable the cache without change your code   |
| \ByJG\Cache\ArrayCacheEngine      | Local cache only using array. It does not persists between requests |
| \ByJG\Cache\FileSystemCacheEngine | Save the cache result in the local file system                      |
| \ByJG\Cache\MemcachedEngine       | Uses the Memcached as the cache engine                              |
| \ByJG\Cache\SessionCachedEngine   | uses the PHP session as cache                                       |
| \ByJG\Cache\ShmopCachedEngine     | uses the shared memory area for cache                               |

## Create new cache instance

### Creating a PSR-6 compatible instance 

You can set instance in the 'cacheconfig.php' setup (see below how to configure the factory)

```php
$cachePool = \ByJG\Cache\Factory::createFilePool();
```

or you can create the CachePool imediatelly:

```php
$cachePool = new CachePool(new FileSystemCacheEngine());
```

### Logging cache commands
 
You can add a PSR Log compatible to the constructor in order to get Log of the operations


### List of Avaiable Factory Commands

**Note: All parameters are optional**

| Engine           | Factory Command                                                     |
|:-----------------|:--------------------------------------------------------------------|
| No Cache         | Factory::createNullPool($prefix, $bufferSize, $logger);             |
| Array            | Factory::createArrayPool($bufferSize, $logger);                     |
| File System      | Factory::createFilePool($prefix, $bufferSize, $logger);             |
| Memcached        | Factory::createMemcachedPool($servers[], $bufferSize, $logger);     |
| Session          | Factory::createSessionPool($prefix, $bufferSize, $logger);          |
| Shmop            | Factory::createShmopPool($config[], $bufferSize, $logger);          |

The Commom parameters are:

- logger: A valid instance that implement the LoggerInterface defined by the PSR/LOG
- bufferSize: the Buffer of CachePool
- prefix: A prefix name to compose the KEY physically 
- servers: An array of memcached servers. E.g.: `[ '127.0.0.1:11211' ]`
- config: Specific setup for shmop. E.g.: `[ 'max-size' => 524288, 'default-permission' => '0700' ]`

## Install

Just type: `composer require "byjg/cache-engine=3.0.*"`


## Running Unit Testes

```
phpunit
```

----
[Open source ByJG](http://opensource.byjg.com)
