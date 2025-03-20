<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

abstract class BaseCacheTest extends TestCase
{
    /**
     * @var \ByJG\Cache\Psr16\BaseCacheEngine
     */
    protected $cacheEngine = null;

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
        $memcachedServer = ['127.0.0.1:11211'];
        $redisCacheServer = '127.0.0.1:6379';
        $redisPassword = '';

        return [
            'Array'         => [
                new \ByJG\Cache\Psr16\ArrayCacheEngine()
            ],
            'FileSystem'    => [
                new \ByJG\Cache\Psr16\FileSystemCacheEngine()
            ],
            'Tmpfs'    => [
                new \ByJG\Cache\Psr16\TmpfsCacheEngine()
            ],
            'ShmopCache'    => [
                new \ByJG\Cache\Psr16\ShmopCacheEngine()
            ],
            'SessionCache'  => [
                new \ByJG\Cache\Psr16\SessionCacheEngine()
            ],
            'NoCacheEngine' => [
                new \ByJG\Cache\Psr16\NoCacheEngine()
            ],
            'Memcached'     => [
                new \ByJG\Cache\Psr16\MemcachedEngine($memcachedServer)
            ],
            'Redis'         => [
                new \ByJG\Cache\Psr16\RedisCacheEngine($redisCacheServer, $redisPassword)
            ],
            'Memory'         => [
                new \ByJG\Cache\Psr16\TmpfsCacheEngine()
            ]
        ];
    }
}
