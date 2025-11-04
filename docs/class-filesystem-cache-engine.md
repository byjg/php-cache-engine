---
sidebar_position: 5
---

# FileSystemCacheEngine

This class uses the filesystem as the cache engine.

## Defining the Path

The FileSystemCacheEngine expects a prefix and a path to store the cache files.
The prefix is used to avoid collision between different applications using the same cache path.
If the path is not defined, the default is the system temporary path.

## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\FileSystemCacheEngine($prefix, $path, $logger, $createPath);
```

**Parameters:**
- `$prefix` (string, default: 'cache'): Prefix to avoid cache key collisions
- `$path` (string|null, default: null): Directory path to store cache files (defaults to system temp directory)
- `$logger` (LoggerInterface|null, default: null): PSR-3 logger instance
- `$createPath` (bool, default: false): Whether to create the path if it doesn't exist

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createFilePool($prefix, $path, $bufferSize, $logger, $createPath);
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\FileSystemCacheEngine($prefix, $path));
```


