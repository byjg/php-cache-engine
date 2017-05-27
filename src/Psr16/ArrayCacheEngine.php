<?php

namespace ByJG\Cache\Psr16;

use Psr\Log\NullLogger;

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
     * Determines whether an item is present in the cache.
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        if (isset($this->cache[$key])) {
            if (isset($this->cache["$key.ttl"]) && time() >= $this->cache["$key.ttl"]) {
                $this->delete($key);
                return false;
            }

            return true;
        }

        return false;
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
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                $key   The key of the item to store.
     * @param mixed                 $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $this->logger->info("[Array cache] Set '$key' in L1 Cache");

        $this->cache[$key] = $value;
        if (!empty($ttl)) {
            $this->cache["$key.ttl"] = $this->addToNow($ttl);
        }

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
     * @return bool
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
        unset($this->cache["$key.ttl"]);
        return true;
    }

    public function isAvailable()
    {
        return true;
    }
}
