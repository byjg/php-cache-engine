# Atomic Operations

Some cache engines allow you to do atomic operations such as incrementing or decrementing a value.

Besides this is not cache operation, it is a common operation in cache engines.

The advantage of using atomic operations is that you can avoid race conditions when multiple processes 
are trying to update the same value.

The atomic operations are:
- Increment: Increment a value by a given number
- Decrement: Decrement a value by a given number
- Add: Add a value to a list in the cache

The engines that support atomic operations have to implement the `AtomicOperationInterface`.

Some engines that support atomic operations are:
- RedisCacheEngine
- MemcachedEngine
- FileSystemCacheEngine
- TmpfsCacheEngine (inherits from FileSystemCacheEngine)

## Increment

The increment operation is used to increment a value by a given number.

```php
<?php
/** @var \ByJG\Cache\AtomicOperationInterface $cache */
$cache->increment('my-key', 1);
```

## Decrement

The decrement operation is used to decrement a value by a given number.

```php
<?php
/** @var \ByJG\Cache\AtomicOperationInterface $cache */
$cache->decrement('my-key', 1);
```

## Add

The add operation is used to add a value to a list in the cache.

```php
<?php
/** @var \ByJG\Cache\AtomicOperationInterface $cache */
$cache->add('my-key', 'value1');
$cache->add('my-key', 'value2');
$cache->add('my-key', 'value3');

print_r($cache->get('my-key')); // ['value1', 'value2', 'value3']
```

