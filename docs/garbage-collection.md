---
sidebar_position: 13
---

# Garbage Collection

Some cache engines need to have a garbage collection process to remove expired keys.

In some engines like `Memcached` and `Redis`, the garbage collection is done automatically by the engine itself.

In other engines like `FileSystem` and `Array`, there is no automatic process. The current implementation
is based on best effort, meaning an expired key is removed only when you try to access it.

If the cache engine has a low hit rate, it is recommended to run a garbage collection process
to prevent the cache from growing indefinitely.

The classes that implement the `GarbageCollectorInterface` have two methods:

- `collectGarbage()` removes every expired key;
- `getTtl(string $key): ?int` returns the moment the key expires, as a Unix timestamp, or `null`
  when the key has no expiry (or does not exist).

**Engines that support garbage collection:**
- FileSystemCacheEngine
- ArrayCacheEngine
- TmpfsCacheEngine (inherits from FileSystemCacheEngine)

## Example

```php
<?php
/** @var \ByJG\Cache\GarbageCollectorInterface $cache */
$cache->collectGarbage();

$expiresAt = $cache->getTtl('my-key'); // e.g. 1791849600, or null
```

:::caution Performance Warning
The garbage collection process is blocking and will be slow if you have many keys to remove.
:::

