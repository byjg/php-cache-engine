---
sidebar_position: 12
---

# Garbage Collection

Some cache engines need to have a garbage collection process to remove expired keys.

In some engines like `Memcached` and `Redis`, the garbage collection is done automatically by the engine itself.

In other engines like `FileSystem` and `Array`, there is no automatic process. The current implementation
is based on best effort, meaning an expired key is removed only when you try to access it.

If the cache engine has a low hit rate, it is recommended to run a garbage collection process
to prevent the cache from growing indefinitely.

The classes that implement the `GarbageCollectorInterface` have the method `collectGarbage()`.

**Engines that support garbage collection:**
- FileSystemCacheEngine
- ArrayCacheEngine
- TmpfsCacheEngine (inherits from FileSystemCacheEngine)

## Example

```php
<?php
/** @var \ByJG\Cache\GarbageCollectorInterface $cache */
$cache->collectGarbage();
```

:::caution Performance Warning
The garbage collection process is blocking and will be slow if you have many keys to remove.
:::

