---
sidebar_position: 10
---

# PhpFileCacheEngine

Each entry is a PHP file (`<?php return [...];`), read with `include`. With OPcache, an entry is read from memory,
not from the disk: a value of scalars and arrays is written as PHP code, which OPcache keeps in shared memory with
nothing to decode, and any other value (an object) is written serialized.

```php
<?php
$cache = new \ByJG\Cache\Psr16\PhpFileCacheEngine(
    'config',      // The start of the file names (default: 'cache')
    '/var/cache'   // The directory (default: the system temp dir); it must exist
);
$cache->set('key', ['a' => 1]);
```

## When to use it

For entries **written once and read on every request**: a DI container (byjg/config's `withCache()`), compiled
data, a configuration. A web request (PHP-FPM) reads them from OPcache instead of opening and unserializing a file.

**Not** for values that change often (sessions, query results, counters): each write compiles the file again, and
OPcache keeps the previous version as wasted memory until it restarts (`opcache.max_wasted_percentage`), which
empties the whole OPcache at once. Use `FileSystemCacheEngine`, `RedisCacheEngine` or `MemcachedEngine` for those.

## Details

- **Files readable by their owner only (0600)**: an entry can hold secrets (a cached configuration has the
  passwords). The user that reads the cache (PHP-FPM) must be the one that writes it.
- **Never read half written**: a file is written to a temporary file and renamed, then invalidated in OPcache
  (`opcache_invalidate()`), so a change is seen even with `opcache.validate_timestamps=0`.
- **TTL**: an expired entry is removed when it is read. There is no garbage collector nor atomic operation.
- A `null` value removes the entry, as `FileSystemCacheEngine` does.
- `isAvailable()` is false when the directory does not exist or cannot be written.
