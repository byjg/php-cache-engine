<?php
namespace ByJG\Cache;


use ByJG\Cache\Engine\ArrayCacheEngine;
use ByJG\Cache\Engine\FileSystemCacheEngine;
use ByJG\Cache\Engine\MemcachedEngine;
use ByJG\Cache\Engine\NoCacheEngine;
use ByJG\Cache\Engine\RedisCacheEngine;
use ByJG\Cache\Engine\SessionCacheEngine;
use ByJG\Cache\Engine\ShmopCacheEngine;
use ByJG\Cache\Psr\CachePool;

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
