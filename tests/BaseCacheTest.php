<?php

namespace Test;

use PHPUnit\Framework\TestCase;

require_once 'Model.php';

abstract class BaseCacheTest extends TestCase
{
    /**
     * @var \ByJG\Cache\Psr16\BaseCacheEngine
     */
    protected $cacheEngine = null;

    protected function tearDown(): void
    {
        if (empty($this->cacheEngine)) {
            return;
        }
        $this->cacheEngine->clear();
        $this->cacheEngine = null;
    }

    public function CachePoolProvider()
    {
        $memcachedServer = ['memcached:11211'];
        $redisCacheServer = 'redis:6379';
        $redisPassword = '';

        return [
            'Array'         => [
                new \ByJG\Cache\Psr16\ArrayCacheEngine()
            ],
            'FileSystem'    => [
                new \ByJG\Cache\Psr16\FileSystemCacheEngine()
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
            ]
        ];
    }
}
