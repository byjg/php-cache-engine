<?php

namespace ByJG\Cache\Engine;

use ByJG\Cache\CacheEngineInterface;
use Memcached;
use Psr\Log\NullLogger;

class RedisCacheEngine implements CacheEngineInterface
{

    /**
     *
     * @var \Redis
     */
    protected $redis = null;

    protected $logger = null;

    protected $server = null;

    protected $password = null;

    public function __construct($server = null, $password = null, $logger = null)
    {
        $this->server = $server;
        if (is_null($server)) {
            $this->server = '127.0.0.1:6379';
        }

        $this->password = $password;

        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    protected function lazyLoadRedisServer()
    {
        if (is_null($this->redis)) {
            $this->redis = new \Redis();
            $data = explode(":", $this->server);
            $this->redis->connect($data[0], isset($data[1]) ? $data[1] : 6379);

            if (!empty($this->password)) {
                $this->redis->auth($this->password);
            }
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

            $this->redis->info('redis_version');
        }
    }

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        $this->lazyLoadRedisServer();

        $value = $this->redis->get($key);
        $this->logger->info("[Redis Cache] Get '$key' result ");

        return ($value === false ? null : $value);
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0)
    {
        $this->lazyLoadRedisServer();

        $this->redis->set($key, $object, $ttl);
        $this->logger->info("[Redis Cache] Set '$key' result ");

        return true;
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function release($key)
    {
        $this->lazyLoadRedisServer();

        $this->redis->delete($key);
    }

    /**
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    public function append($key, $str)
    {
        $this->lazyLoadRedisServer();

        $this->logger->info("[Redis Cache] Append '$key' in Memcached");
        return $this->redis->append($key, $str);
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key)
    {
        $this->lazyLoadRedisServer();

        return;
    }

    /**
     * UnLock resource after set it
     * @param string $key
     */
    public function unlock($key)
    {
        $this->lazyLoadRedisServer();

        return;
    }

    public function isAvailable()
    {
        if (!class_exists('\Redis')) {
            return false;
        }

        try {
            $this->lazyLoadRedisServer();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
