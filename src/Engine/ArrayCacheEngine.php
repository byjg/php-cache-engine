<?php

namespace ByJG\Cache\Engine;

use Psr\Log\NullLogger;
use Psr\SimpleCache\DateInterval;

class ArrayCacheEngine extends BaseCacheEngine
{

    protected $cache = array();
    
    protected $logger = null;
    
    public function __construct($logger = null)
    {
        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->cache[$key]);
    }

    /**
     * @param string $key The object KEY
     * @param mixed $default IGNORED IN MEMCACHED.
     * @return mixed Description
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            $this->logger->info("[Array cache] Get '$key' from L1 Cache");
            return $this->cache[$key];
        } else {
            $this->logger->info("[Array cache] Not found '$key'");
            return $default;
        }
    }

    /**
     * @param string $key The object Key
     * @param object $value The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $value, $ttl = 0)
    {
        $this->logger->info("[Array cache] Set '$key' in L1 Cache");

        $this->cache[$key] = $value;

        return true;
    }

    public function clear()
    {
        $this->cache = [];
    }

    /**
     * Unlock resource
     *
     * @param string $key
     * @return bool|void
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
        return true;
    }

    public function isAvailable()
    {
        return true;
    }
}
