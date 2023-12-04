# Class SessionCacheEngine

This class uses the PHP Session as the cache engine. 
This will persist the cache between requests while the user session is active.

The cache is not shared between different users.


## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\SessionCacheEngine($prefix)
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createSessionPool($prefix, $bufferSize = 10)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\SessionCacheEngine($prefix));
```


