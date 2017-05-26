<?php

namespace ByJG\Cache\Engine;

use ByJG\Cache\CacheEngineInterface;
use Memcached;
use Psr\Log\NullLogger;

class MemcachedEngine implements CacheEngineInterface
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
     * @return object Description
     */
    public function get($key, $default = 0)
    {
        $this->lazyLoadMemCachedServers();

        $value = $this->memCached->get($key);
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->info("[Memcached] Cache '$key' missed with status " . $this->memCached->getResultCode());
            return null;
        }

        return $value;
    }

    /**
     * @param string $key The object Key
     * @param object $value The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $value, $ttl = 0)
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->set($key, $value, $ttl);
        $this->logger->info("[Memcached] Set '$key' result " . $this->memCached->getResultCode());
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->error("[Memcached] Set '$key' failed with status " . $this->memCached->getResultCode());
        }

        return $this->memCached->getResultCode() === Memcached::RES_SUCCESS;
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function delete($key)
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->delete($key);
    }

    /**
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    public function append($key, $str)
    {
        $this->lazyLoadMemCachedServers();

        $this->logger->info("[Memcached] Append '$key' in Memcached");
        return $this->memCached->append($key, $str);
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key)
    {
        $this->lazyLoadMemCachedServers();

        return;
    }

    /**
     * UnLock resource after set it
     * @param string $key
     */
    public function unlock($key)
    {
        $this->lazyLoadMemCachedServers();

        return;
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
}
