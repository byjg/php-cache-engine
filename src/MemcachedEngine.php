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

    protected $logger = null;

    public function __construct($logger = null)
    {
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    protected function lazyLoadMemCachedServers()
    {
        if (is_null($this->_memCached)) {
            $configKey = isset($this->configKey) ? $this->configKey : 'default';
            $config = CacheContext::getInstance()->getMemcachedConfig($configKey);

            if (empty($config)) {
                throw new InvalidArgumentException("Key '$configKey' does not exists in '" . getcwd() . "/config/cacheconfig.php'");
            }
            if (!isset($config['servers'])) {
                throw new InvalidArgumentException("The config 'servers' is not set in 'config/cacheconfig.php'");
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

        
        if (CacheContext::getInstance()->getReset()) {
            $this->logger->info("[Memcached] Get $key failed because RESET=true");
            return null;
        }

        if (CacheContext::getInstance()->getNoCache()) {
            $this->logger->info("[Memcached] Failed to get $key because NOCACHE=true");
            return null;
        }

        $value = $this->_memCached->get($key);
        if ($this->_memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->info("[Memcached] Cache '$key' missed with status " . $this->_memCached->getResultCode());
            return null;
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

        

        if (!CacheContext::getInstance()->getNoCache()) {
            $this->_memCached->set($key, $object, $ttl);
            $this->logger->info("[Memcached] Set '$key' result " . $this->_memCached->getResultCode());
            if ($this->_memCached->getResultCode() !== Memcached::RES_SUCCESS) {
                $this->logger->error("[Memcached] Set '$key' failed with status " . $this->_memCached->getResultCode());
            }

            return $this->_memCached->getResultCode() === Memcached::RES_SUCCESS;
        } else {
            $this->logger->info("[Memcached] Not Set '$key' because NOCACHE=true");
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

        

        if (!CacheContext::getInstance()->getNoCache()) {
            $this->logger->info("[Memcached] Append '$key' in Memcached");
            return $this->_memCached->append($key, $str);
        } else {
            $this->logger->info("[Memcached] Not Set '$key' because NOCACHE=true");
        }
        
        return true;
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
