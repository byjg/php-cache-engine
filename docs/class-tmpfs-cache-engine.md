# Class TmpfsCacheEngine

This class uses the Tmpfs as the cache engine.

## Defining the Path

The TmpfsCacheEngine allows to store the cache files in the `/dev/shm` tmpfs.


## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\TmpfsCacheEngine($prefix, $logger)
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createTmpfsCachePool($prefix, $logger)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\TmpfsCacheEngine($prefix, $logger));
```


