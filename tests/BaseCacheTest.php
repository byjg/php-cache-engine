<?php

namespace Test;

use ByJG\Cache\Psr6\CachePool;

require_once 'Model.php';

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

abstract class BaseCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \ByJG\Cache\Engine\BaseCacheEngine
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
                new \ByJG\Cache\Engine\ArrayCacheEngine()
            ],
            'FileSystem'    => [
                new \ByJG\Cache\Engine\FileSystemCacheEngine()
            ],
            'ShmopCache'    => [
                new \ByJG\Cache\Engine\ShmopCacheEngine()
            ],
            'SessionCache'  => [
                new \ByJG\Cache\Engine\SessionCacheEngine()
            ],
            'NoCacheEngine' => [
                new \ByJG\Cache\Engine\NoCacheEngine()
            ],
            'Memcached'     => [
                new \ByJG\Cache\Engine\MemcachedEngine($memcachedServer)
            ],
            'Redis'         => [
                new \ByJG\Cache\Engine\RedisCacheEngine($redisCacheServer, $redisPassword)
            ]
        ];
    }
}
