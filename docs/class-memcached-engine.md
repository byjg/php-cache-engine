# Class MemcachedEngine

This class uses the Memcached as the cache engine.

## Defining the Servers

The constructor expects an array of servers. 
Each server is an item in the array with the following format:

```php
$servers = [
    'localhost:11211',
]
```

## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\MemcachedEngine($servers)
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createMemcachedPool($servers)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\MemcachedEngine($servers));
```


