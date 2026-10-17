---
sidebar_position: 7
---

# RedisCacheEngine

This class uses Redis as the cache engine.

## Defining the Servers

The constructor expects a string with the server and port.

```php
$server = 'localhost:5678'
```

When `$server` is `null` it defaults to `127.0.0.1:6379`; a server without a port uses `6379`.

## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\RedisCacheEngine($server, $password, $logger)
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createRedisPool($server, $password, $bufferSize, $logger)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\RedisCacheEngine($server, $password, $logger));
```


