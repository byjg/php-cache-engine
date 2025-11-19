---
sidebar_position: 10
---

# ShmopCacheEngine

:::warning Deprecated
This engine is deprecated. Use [TmpfsCacheEngine](class-tmpfs-cache-engine.md) instead for better performance and reliability.
:::

This class uses PHP Shmop (shared memory) as the cache engine.

Shared memory allows multiple processes to access the same data in memory.
You can use it to share data among running PHP scripts on the same server.

## Configuration

These are the default values for the configuration:

```php
$config = [
    'max-size' => 524288, // 512KB
    'default-permission' => '0700',
];
```

## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\ShmopCacheEngine($config, $logger);
```

**Parameters:**
- `$config` (array, default: []): Configuration options for shared memory
- `$logger` (LoggerInterface|null, default: null): PSR-3 logger instance

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createShmopPool($config, $bufferSize, $logger);
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\ShmopCacheEngine($config, $logger));
```


