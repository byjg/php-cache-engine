# Class ShmopCacheEngine

This class uses the PHP Shmop as the cache engine. 

The Shared memory allows multiple processes to access the same data in memory. 
You can use it to share data among running PHP scripts in the same server.

## Configuration

These are the default values for the configuration:

```php
$config = [
    'max-size' => 524288, // 512Kb
    'default-permission' = > '0700', 
];
```


## PSR-16 Constructor

```php
$cache = new \ByJG\Cache\Psr16\ShmopCacheEngine($config, $prefix)
```

## PSR-6 Constructor

```php
$cachePool = \ByJG\Cache\Factory::createSessionPool($prefix, $bufferSize = 10)
```

or

```php
$cachePool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\ShmopCacheEngine($config, $prefix));
```


