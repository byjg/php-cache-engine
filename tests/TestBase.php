<?php

namespace Tests;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
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
                EngineFactory::memcached()
            ],
            'Redis'         => [
                EngineFactory::redis()
            ],
            'Memory'         => [
                new TmpfsCacheEngine()
            ]
        ];
    }
}
