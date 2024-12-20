# Garbage Collection

Some cache engines need to have a garbage collection process to remove the expired keys.

In some engines like `Memcached` and `Redis` the garbage collection is done automatically by the engine itself.

In other engines like `FileSystem` and `Array` there is no such process. The current implementation
is based on the Best Effort. It means an expired key is removed only when you try to access it.

If the cache engine has a low hit rate, it is recommended to run a garbage collection process
to avoid the cache to grow indefinitely.

The classes that implement the `GarbageCollectionInterface` have the method `collectGarbage()`.

Some engines that support garbage collection are:
- FileSystemCacheEngine
- ArrayCacheEngine
- TmpfsCacheEngine

## Example

```php
<?php
/** @var \ByJG\Cache\GarbageCollectionInterface $cache */
$cache->collectGarbage();
```

Note: The garbage collection process is blocking. 
It means the process will be slow if you have a lot of keys to remove.

