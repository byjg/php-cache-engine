# Cache Engine
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f643fd22-8ab1-4f41-9bef-f9f9e127ec0d/mini.png)](https://insight.sensiolabs.com/projects/f643fd22-8ab1-4f41-9bef-f9f9e127ec0d)

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

### PSR-6 cache pool

You can set instance in the 'cacheconfig.php' setup (see below and use the factory)

```php
$cachePool = \ByJG\Cache\CacheContext::psrFactory();
```

or you can create the CachePool imediatelly:

```php
$cachePool = new CachePool(new FileSystemCacheEngine());
```


### Accessing the Cache Engine directory

You can create a instance from the Cache engine directly. This is not PSR-6 compliant, but implements
features that the CachePool does not support and it is for backward compatibilty also.

You can create from the factory and cacheconfig.php file:

```php
$cacheEngine = \ByJG\Cache\CacheContext::factory();
```

or instantiate directly

```php
$cacheEngine = new \ByJG\Cache\MemcachedEngine();
```

## Install

Just type: `composer require "byjg/cache-engine=2.0.*"`

## Setup the config

You need to have a file named 'config/cacheconfig.php' with the follow contents:

### Basic Configuration

```php
return [
    'default' => [
        'instance' => '\\ByJG\\Cache\\NoCacheEngine'
    ]
];
```
The parameters are described below:
* 'default' is the name of the key used in the CacheContext::factory(key)
* 'instance' is required if you use CacheContext::factory. Must have the full name space for the cache class;

### Specific configuration for Memcached

```php
return [
    'default' => [
        'instance' => '\\ByJG\\Cache\\MemcachedEngine',
        'memcached' => [
            'servers' => [
                '127.0.0.1:11211'
            ]
        ],
    ]
];
```

The parameters are described below:
* 'memcached' have specific configuration for the MemcachedEngine class.

### Specific configuration for Shmop Cache

```php
return [
    'default' => [
        'instance' => '\\ByJG\\Cache\\ShmopCacheEngine',
        'shmop' => [
            'max-size' => 1048576,
            'default-permission' => '0700'
        ]
    ]
];
```

The parameters are described below:
* 'shmop' have specific configuration for the ShmopCacheEngine class.




----
[Open source ByJG](http://opensource.byjg.com)
