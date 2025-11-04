---
sidebar_position: 6
---

# MemcachedEngine

This class uses Memcached as the cache engine.

## Defining the Servers

The constructor expects an array of servers. 
Each server can be provided in one of the following formats:

```php
$servers = [
    'localhost:11211',
    ['host.example', 11211],
];
```

You can also pass Memcached client options (no need to pass a Memcached instance). Options can be provided as an associative array where the keys are Memcached option constants or their string names:

```php
$options = [
    \Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
    \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
    \Memcached::OPT_REMOVE_FAILED_SERVERS => true,
    \Memcached::OPT_CONNECT_TIMEOUT => 100, // ms
    // Or using string keys:
    'OPT_CONNECT_TIMEOUT' => 100,
];
```

## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\MemcachedEngine($servers, null, $options);
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createMemcachedPool($servers, 10, null, $options)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\MemcachedEngine($servers, null, $options));
```


