# Class FilesystemCacheEngine

This class uses the Filesystem as the cache engine.

## Defining the Path

The FileSystemCacheEngine expects a prefix and a path to store the cache files.
The prefix is used to avoid collision between different applications using the same cache path.
If the path is not defined, the default is the system temporary path.


## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\FileSystemCacheEngine($path, $prefix)
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createFilePool($path, $prefix, $bufferSize = 10)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\FileSystemCacheEngine($path, $prefix));
```


