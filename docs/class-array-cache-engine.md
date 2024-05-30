# Class ArrayCacheEngine

This class is a simple cache engine that uses an array to store the values. 
It does not persist between requests. 

It is ideal to use on unit tests or when you need a simple cache engine.


## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\ArrayCacheEngine()
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createArrayPool()
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\ArrayCacheEngine());
```


