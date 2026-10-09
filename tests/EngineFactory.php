<?php

namespace Tests;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\CompareAndSwapInterface;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;

/**
 * Builds the network-backed engines from the environment.
 *
 * Locally the defaults point at docker-compose on localhost. In CI the job runs inside a container
 * and the services sit elsewhere on the Docker network, reachable by service name rather than
 * loopback - so every test that needs Redis or Memcached has to come through here rather than
 * calling `new RedisCacheEngine()` and hardcoding 127.0.0.1.
 */
class EngineFactory
{
    public const REDIS = 'redis';
    public const MEMCACHED = 'memcached';

    public static function make(string $engine): BaseCacheEngine&AtomicOperationInterface&CompareAndSwapInterface
    {
        return match ($engine) {
            self::REDIS => self::redis(),
            self::MEMCACHED => self::memcached(),
            default => throw new \InvalidArgumentException("Unknown engine '$engine'"),
        };
    }

    public static function redis(): RedisCacheEngine
    {
        return new RedisCacheEngine(
            getenv('REDIS_SERVER') ?: '127.0.0.1:6379',
            getenv('REDIS_PASSWORD') ?: ''
        );
    }

    public static function memcached(): MemcachedEngine
    {
        return new MemcachedEngine([getenv('MEMCACHED_SERVER') ?: '127.0.0.1:11211']);
    }
}
