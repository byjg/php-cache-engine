<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheEngineInterface;
use Memcached;
use Psr\Log\NullLogger;

class MemcachedEngine extends BaseCacheEngine
{

    /**
     *
     * @var Memcached
     */
    protected $memCached = null;

    protected $logger = null;

    protected $servers = null;

    public function __construct($servers = null, $logger = null)
    {
        $this->servers = (array)$servers;
        if (is_null($servers)) {
            $this->servers = [
                '127.0.0.1:11211'
            ];
        }

        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    protected function fixKey($key) {
        return "cache-" . $key;
    }

    protected function lazyLoadMemCachedServers()
    {
        if (is_null($this->memCached)) {
            $this->memCached = new Memcached();
            foreach ($this->servers as $server) {
                $data = explode(":", $server);
                $this->memCached->addServer($data[0], $data[1]);

                $stats = $this->memCached->getStats();
                if (!isset($stats[$server]) || $stats[$server]['pid'] === -1) {
                    throw new \Exception("Memcached server $server is down");
                }
            }
        }
    }

    /**
     * @param string $key The object KEY
     * @param int $default IGNORED IN MEMCACHED.
     * @return mixed Description
     */
    public function get($key, $default = null)
    {
        $this->lazyLoadMemCachedServers();

        $value = $this->memCached->get($this->fixKey($key));
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->info("[Memcached] Cache '$key' missed with status " . $this->memCached->getResultCode());
            return $default;
        }

        return unserialize($value);
    }

    /**
     * @param string $key The object Key
     * @param object $value The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $value, $ttl = null)
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->set($this->fixKey($key), serialize($value), $ttl);
        $this->logger->info("[Memcached] Set '$key' result " . $this->memCached->getResultCode());
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->error("[Memcached] Set '$key' failed with status " . $this->memCached->getResultCode());
        }

        return $this->memCached->getResultCode() === Memcached::RES_SUCCESS;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->delete($this->fixKey($key));
        return true;
    }

    public function isAvailable()
    {
        if (!class_exists('\Memcached')) {
            return false;
        }

        try {
            $this->lazyLoadMemCachedServers();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function clear()
    {
        $result = $this->memCached->flush();
        return $result;
    }

    public function has($key)
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->get($this->fixKey($key));
        return ($this->memCached->getResultCode() === Memcached::RES_SUCCESS);
    }
}
