<?php

namespace Test;

require_once 'Model.php';

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

abstract class BaseCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \ByJG\Cache\Psr16\BaseCacheEngine
     */
    protected $cacheEngine = null;

    protected function setUp()
    {

    }

    protected function tearDown()
    {
        $this->cacheEngine->clear();
        $this->cacheEngine = null;
    }

    public function CachePoolProvider()
    {
        $memcachedServer = ['memcached-container:11211'];
        $redisCacheServer = 'redis-container:6379';
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
