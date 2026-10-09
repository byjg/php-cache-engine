---
sidebar_position: 11
---

# Atomic Operations

Some cache engines allow you to do atomic operations such as incrementing or decrementing a value.

Although this is not a traditional cache operation, it is a common operation in cache engines.

The advantage of using atomic operations is that you can avoid race conditions when multiple processes
are trying to update the same value.

**Available atomic operations:**
- **Increment**: Increment a value by a given number
- **Decrement**: Decrement a value by a given number
- **Add**: Add a value to a list in the cache

The engines that support atomic operations implement the `AtomicOperationInterface`.

**Engines that support atomic operations:**
- RedisCacheEngine
- MemcachedEngine
- FileSystemCacheEngine
- TmpfsCacheEngine (inherits from FileSystemCacheEngine)

These operations read and modify a value in one step. If what you need instead is a *conditional*
write — "store this only if the key is free", "delete this only if it is still mine" — see
[Compare and Swap](compare-and-swap.md).

All three operations accept an optional TTL, expressed in seconds from now (or as a `DateInterval`),
and apply it as part of the same operation.

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

If the key already holds a plain value written by `set()`, the first `add()` converts it into a
list and keeps the original value as the first element:

```php
<?php
$cache->set('my-key', 'value1');
$cache->add('my-key', 'value2');

print_r($cache->get('my-key')); // ['value1', 'value2']
```


