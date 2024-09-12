# Basic Usage - Psr6 Cache Pool

## Get an element from cache (using Factory...)

```php
<?php
$pool = \ByJG\Cache\Factory::createMemcachedPool();
$item = $pool->getItem('mykey');
if (!$item->isHit()) {
    // Do the operations will be cached
    // ....
    // And set variable '$value'
    $value = "...";
    $item->set($value);
    $item->expiresAfter(3600);

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