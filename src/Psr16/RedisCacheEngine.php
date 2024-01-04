<?php

namespace ByJG\Cache\Psr16;

use Psr\Log\NullLogger;

class RedisCacheEngine extends BaseCacheEngine
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

    protected function fixKey($key) {
        $key = $this->getKeyFromContainer($key);
        return "cache:$key";
    }

    /**
     * @param string $key The object KEY
     * @param int $default IGNORED IN MEMCACHED.
     * @return mixed Description
     */
    public function get($key, $default = null)
    {
        $this->lazyLoadRedisServer();

        $value = $this->redis->get($this->fixKey($key));
        $this->logger->info("[Redis Cache] Get '$key' result ");

        return ($value === false ? $default : unserialize($value));
    }

    /**
     * @param string $key The object Key
     * @param object $value The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $value, $ttl = null)
    {
        $this->lazyLoadRedisServer();

        $ttl = $this->convertToSeconds($ttl);

        $this->redis->set($this->fixKey($key), serialize($value), $ttl);
        $this->logger->info("[Redis Cache] Set '$key' result ");

        return true;
    }

    public function delete($key)
    {
        $this->lazyLoadRedisServer();

        $this->redis->del($this->fixKey($key));

        return true;
    }

    public function clear()
    {
        $keys = $this->redis->keys('cache:*');
        foreach ((array)$keys as $key) {
            if (preg_match('/^cache:(?<key>.*)/', $key, $matches)) {
                $this->delete($matches['key']);
            }
        }
    }

    public function has($key)
    {
        $result = $this->redis->exists($this->fixKey($key));

        if (is_numeric($result)) {
            return $result !== 0;
        }

        return $result;
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
