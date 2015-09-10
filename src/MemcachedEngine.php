<?php

namespace ByJG\Cache;

use InvalidArgumentException;
use Memcached;

class MemcachedEngine implements CacheEngineInterface
{

    /**
     *
     * @var Memcached
     */
    protected $_memCached = null;

    protected function lazyLoadMemCachedServers()
    {
        if (is_null($this->_memCached)) {
            $config = CacheContext::getInstance()->getMemcachedConfig(isset($this->configKey) ? $this->configKey : 'default');

            if (empty($config) || !isset($config['servers'])) {
                throw new InvalidArgumentException("You have to configure the memcached servers in the file 'config/cacheconfig.php'");
            }

            $servers = $config['servers'];

            $this->_memCached = new Memcached();
            foreach ($servers as $server) {
                $data = explode(":", $server);
                $this->_memCached->addServer($data[0], $data[1]);

                $stats = $this->_memCached->getStats();
                if (!isset($stats[$server]) || $stats[$server]['pid'] === -1) {
                    throw new \Exception("Memcached server $server is down");
                }
            }
        }
    }

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        $this->lazyLoadMemCachedServers();

        $log = LogHandler::getInstance();
        if (CacheContext::getInstance()->getReset()) {
            $log->info("[Memcached] Get $key failed because RESET=true");
            return false;
        }

        if (CacheContext::getInstance()->getNoCache()) {
            $log->info("[Memcached] Failed to get $key because NOCACHE=true");
            return false;
        }

        $value = $this->_memCached->get($key);
        if ($this->_memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $log->info("[Memcached] Cache '$key' missed with status " . $this->_memCached->getResultCode());
            return false;
        }

        return $value;
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0)
    {
        $this->lazyLoadMemCachedServers();

        $log = LogHandler::getInstance();

        if (!CacheContext::getInstance()->getNoCache()) {
            $this->_memCached->set($key, $object, $ttl);
            $log->info("[Memcached] Set '$key' result " . $this->_memCached->getResultCode());
            if ($this->_memCached->getResultCode() !== Memcached::RES_SUCCESS) {
                $log->error("[Memcached] Set '$key' failed with status " . $this->_memCached->getResultCode());
            }

            return $this->_memCached->getResultCode() === Memcached::RES_SUCCESS;
        } else {
            $log->info("[Memcached] Not Set '$key' because NOCACHE=true");
            return true;
        }
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function release($key)
    {
        $this->lazyLoadMemCachedServers();

        $this->_memCached->delete($key);
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

        $log = LogHandler::getInstance();

        if (!CacheContext::getInstance()->getNoCache()) {
            $log->info("[Memcached] Append '$key' in Memcached");
            return $this->_memCached->append($key, $str);
        } else {
            $log->info("[Memcached] Not Set '$key' because NOCACHE=true");
        }
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
}
