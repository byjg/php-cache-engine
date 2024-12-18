# Basic Usage - Psr16 Simple Cache

Psr16 is a standard for cache in PHP with less verbosity than Psr6.

You can just instantiate the cache engine and use it as you can see below. 

## Get an element from cache and set if not exists

```php
<?php
$cacheEngine = new \ByJG\Cache\Psr16\FileSystemCacheEngine();

$result = $cacheEngine->get($key);
if (empty($result))
{
    // Do the operations will be cached
    // ....
    // And set variable result
    $result = "...";

    // Set the cache:
    $cacheEngine->set($key, $result, 60);
}
return $result;
```

## Check if an element exists in cache

```php
<?php
$cacheEngine = new \ByJG\Cache\Psr16\FileSystemCacheEngine();
if ($cacheEngine->has($key)) {
    // ...
}
```    


## Delete an element from cache

```php
<?php
$cacheEngine = new \ByJG\Cache\Psr16\FileSystemCacheEngine();
$cacheEngine->delete($key);
```

## Clear all cache

```php
<?php
$cacheEngine = new \ByJG\Cache\Psr16\FileSystemCacheEngine();
$cacheEngine->clear();
```

