<?php

namespace Tests;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;
use ByJG\Cache\Psr16\SessionCacheEngine;
use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr16\TmpfsCacheEngine;
use PHPUnit\Framework\TestCase;

abstract class TestBase extends TestCase
{
    /**
     * @var BaseCacheEngine|null
     */
    protected ?BaseCacheEngine $cacheEngine = null;

    #[\Override]
    protected function tearDown(): void
    {
        if (empty($this->cacheEngine)) {
            return;
        }
        $this->cacheEngine->clear();
        $this->cacheEngine = null;
    }

    public static function CachePoolProvider()
    {
        if (getenv('MEMCACHED_SERVER')) {
            $memcachedServer = [getenv('MEMCACHED_SERVER')];
        } else {
            $memcachedServer = ['127.0.0.1:11211'];
        }
        if (getenv('REDIS_SERVER')) {
            $redisCacheServer = getenv('REDIS_SERVER');
        } else {
            $redisCacheServer = '127.0.0.1:6379';
        }
        if (getenv('REDIS_PASSWORD')) {
            $redisPassword = getenv('REDIS_PASSWORD');
        } else {
            $redisPassword = '';
        }

        return [
            'Array'         => [
                new ArrayCacheEngine()
            ],
            'FileSystem'    => [
                new FileSystemCacheEngine()
            ],
            'Tmpfs'    => [
                new TmpfsCacheEngine()
            ],
            'ShmopCache'    => [
                new ShmopCacheEngine()
            ],
            'SessionCache'  => [
                new SessionCacheEngine()
            ],
            'NoCacheEngine' => [
                new NoCacheEngine()
            ],
            'Memcached'     => [
                new MemcachedEngine($memcachedServer)
            ],
            'Redis'         => [
                new RedisCacheEngine($redisCacheServer, $redisPassword)
            ],
            'Memory'         => [
                new TmpfsCacheEngine()
            ]
        ];
    }
}
