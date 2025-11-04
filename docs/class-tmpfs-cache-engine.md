---
sidebar_position: 9
---

# TmpfsCacheEngine

This class uses tmpfs as the cache engine.

## Defining the Path

The TmpfsCacheEngine stores cache files in the `/dev/shm` tmpfs (temporary file system in RAM).

:::info
This engine extends FileSystemCacheEngine and automatically uses `/dev/shm` as the storage path for better performance.
:::

## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\TmpfsCacheEngine($prefix, $logger);
```

**Parameters:**
- `$prefix` (string, default: 'cache'): Prefix to avoid cache key collisions
- `$logger` (LoggerInterface|null, default: null): PSR-3 logger instance

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createTmpfsCachePool($prefix, $logger);
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\TmpfsCacheEngine($prefix, $logger));
```


