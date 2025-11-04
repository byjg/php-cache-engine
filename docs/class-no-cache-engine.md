---
sidebar_position: 3
---

# NoCacheEngine

This class doesn't cache. Use it to disable the cache without changing your code.


## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\NoCacheEngine();
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createNullPool();
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\NoCacheEngine());
```


