# Cache Engine

## Description

A multi-purpose cache engine in PHP.

## Examples

### Using Factory

This option will get the engine from the config file. See below.

```php
$cacheEngine = \ByJG\Cache\CacheContext::factory();
```

### Instantiate directly

```php
$cacheEngine = \ByJG\Cache\FileSystemCacheEngine::getInstace();
```

### Basic Usage

```php
$result = $cacheEngine->get($key, 60);
if ($result === false)
{
    // Do the operations will be cached
    // ....
    // And set variable result
    $result = ...;

    // Set the cache:
    $cacheEngine->set($key, $result, 60);
}
return $result;
```

## Install

Just type: `composer require "byjg/cache-engine=~1.0"`

## Setup the config

You need to have a file named 'config/cacheconfig.php' with the follow contents:

```php
return [
    'default' => [
        'instance' => '\\ByJG\\Cache\\NoCacheEngine',
        'memcached' => [
            'servers' => [
                '127.0.0.1:11211'
            ]
        ],
        'shmop' => [
            'max-size' => 1048576,
            'default-permission' => '0700'
        ]
    ]
];
```

The parameters are described below:
* 'default' is the name of the key used in the CacheContext::factory(key)
* 'instance' is required if you use CacheContext::factory. Must have the full name space for the cache class;
* 'memcached' have specific configuration for the MemcachedEngine class. 
* 'shmop' have specific configuration for the ShmopCacheEngine class.


## Avaible cache engines

| Class                             | Description                                                         |
|:----------------------------------|:--------------------------------------------------------------------|
| \ByJG\Cache\NoCacheEngine         | Do nothing. Use it for disable the cache without change your code   |
| \ByJG\Cache\ArrayCacheEngine      | Local cache only using array. It does not persists between requests |
| \ByJG\Cache\FileSystemCacheEngine | Save the cache result in the local file system                      |
| \ByJG\Cache\MemcachedEngine       | Uses the Memcached as the cache engine                              |
| \ByJG\Cache\SessionCachedEngine   | uses the PHP session as cache                                       |
| \ByJG\Cache\ShmopCachedEngine     | uses the shared memory area for cache                               |



----
[Open source ByJG](http://opensource.byjg.com)
