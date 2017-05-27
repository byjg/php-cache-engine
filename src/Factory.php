<?php
namespace ByJG\Cache;


use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;
use ByJG\Cache\Psr16\SessionCacheEngine;
use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr6\CachePool;

class Factory
{
    public static function createNullPool()
    {
        return new CachePool(
            new NoCacheEngine()
        );
    }

    public static function createSessionPool($prefix = null, $bufferSize = null)
    {
        return new CachePool(
            new SessionCacheEngine($prefix),
            $bufferSize
        );
    }

    public static function createFilePool($prefix = null, $bufferSize = null, $logger = null)
    {
        return new CachePool(
            new FileSystemCacheEngine($prefix, $logger),
            $bufferSize
        );
    }

    public static function createShmopPool($config = [], $bufferSize = null, $logger = null)
    {
        return new CachePool(
            new ShmopCacheEngine($config, $logger),
            $bufferSize
        );
    }

    public static function createArrayPool($bufferSize = null, $logger = null)
    {
        return new CachePool(
            new ArrayCacheEngine($logger),
            $bufferSize
        );
    }

    public static function createMemcachedPool($servers = null, $bufferSize = null, $logger = null)
    {
        return new CachePool(
            new MemcachedEngine($servers, $logger),
            $bufferSize
        );
    }

    public static function createRedisCacheEngine($servers = null, $password = null, $bufferSize = null, $logger = null)
    {
        return new CachePool(
            new RedisCacheEngine($servers, $password, $logger),
            $bufferSize
        );
    }

}
