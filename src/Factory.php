<?php
namespace ByJG\Cache;


use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\TmpfsCacheEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;
use ByJG\Cache\Psr16\SessionCacheEngine;
use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr6\CachePool;
use Psr\Log\LoggerInterface;

class Factory
{
    public static function createNullPool(): CachePool
    {
        return new CachePool(
            new NoCacheEngine()
        );
    }

    public static function createSessionPool(string $prefix = 'cache', int $bufferSize = 10): CachePool
    {
        return new CachePool(
            new SessionCacheEngine($prefix),
            $bufferSize
        );
    }

    public static function createFilePool(string $prefix = 'cache', ?string $path = null, int $bufferSize = 10, ?LoggerInterface $logger = null, bool $createPath = false): CachePool
    {
        return new CachePool(
            new FileSystemCacheEngine($prefix, $path, $logger, $createPath),
            $bufferSize
        );
    }

    public static function createShmopPool(array $config = [], int $bufferSize = 10, ?LoggerInterface $logger = null): CachePool
    {
        return new CachePool(
            new ShmopCacheEngine($config, $logger),
            $bufferSize
        );
    }

    public static function createArrayPool(int $bufferSize = 10, ?LoggerInterface $logger = null): CachePool
    {
        return new CachePool(
            new ArrayCacheEngine($logger),
            $bufferSize
        );
    }

    public static function createMemcachedPool(?array $servers = null, int $bufferSize = 10, ?LoggerInterface $logger = null): CachePool
    {
        return new CachePool(
            new MemcachedEngine($servers, $logger),
            $bufferSize
        );
    }

    public static function createRedisCacheEngine(?string $servers = null, ?string $password = null, int $bufferSize = 10, ?LoggerInterface $logger = null): CachePool
    {
        return new CachePool(
            new RedisCacheEngine($servers, $password, $logger),
            $bufferSize
        );
    }

    public static function createTmpfsCachePool(?LoggerInterface $logger = null): CachePool
    {
        return new CachePool(
            new TmpfsCacheEngine($logger)
        );
    }

}
