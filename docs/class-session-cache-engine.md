---
sidebar_position: 8
---

# SessionCacheEngine

This class uses the PHP Session as the cache engine.
This will persist the cache between requests while the user session is active.

The cache is not shared between different users.

Every entry is stored in `$_SESSION` under `<prefix>-<key>`. `clear()` removes only the entries of
that prefix; the session itself, and anything else your application keeps in it, is left alone.


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


