---
sidebar_position: 2
---

# Basic Usage - PSR-6 Cache Pool

## Get an element from cache (using Factory)

```php
<?php
$pool = \ByJG\Cache\Factory::createMemcachedPool();
$item = $pool->getItem('mykey');
if (!$item->isHit()) {
    // Do the operations that will be cached
    // ....
    // And set variable '$value'
    $value = "...";
    $item->set($value);
    $item->expiresAfter(3600); // Cache for 1 hour

    $pool->save($item);
}
return $item->get();
```

## Clear an element from cache

```php
<?php
$pool = \ByJG\Cache\Factory::createMemcachedPool();
$pool->deleteItem('mykey');
```

## Create an Element from PSR-16 Interface:

```php
<?php
$pool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\RedisCacheEngine());
```

## The buffer

`CachePool` keeps the last items it read or saved in memory, so asking for the same key again in
the same request does not go back to the storage engine. `$bufferSize` is how many items it keeps
(default 10); when the buffer is full the oldest item is dropped. Set it to `0` to disable the
buffer and always read from the engine.

Every `Factory::create*Pool()` method except `createNullPool()` and `createTmpfsCachePool()`
accepts `$bufferSize`, and so does the `CachePool` constructor:

```php
<?php
$pool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\RedisCacheEngine(), 50);

$pool->setBufferSize(0);         // disable the buffer
$size = $pool->getBufferSize();  // 0
```

## Check if the engine is available

```php
<?php
if (!$pool->isAvailable()) {
    // The underlying engine cannot be used (extension missing, server down, ...)
}
```
