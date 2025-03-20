# Class RedisCacheEngine

This class uses the Redis as the cache engine.

## Defining the Servers

The constructor expects a string with the server and port.

```php
$server = 'localhost:5678'
```

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


