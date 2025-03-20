<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\GarbageCollectorInterface;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ArrayCacheEngine extends BaseCacheEngine implements GarbageCollectorInterface
{

    protected array $cache = [
        "ttl" => []
    ];
    
    protected LoggerInterface|null $logger = null;
    
    public function __construct(LoggerInterface|null $logger = null)
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
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function has(string $key): bool
    {
        $key = $this->getKeyFromContainer($key);
        if (isset($this->cache[$key])) {
            if (isset($this->cache['ttl']["$key"]) && time() >= $this->cache["ttl"]["$key"]) {
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
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            $key = $this->getKeyFromContainer($key);
            $this->logger->info("[Array cache] Get '$key' from L1 Cache");
            return unserialize($this->cache[$key]);
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
     * @param null|int|DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     *   MUST be thrown if the $key string is not a legal value.
     */
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key = $this->getKeyFromContainer($key);

        $this->logger->info("[Array cache] Set '$key' in L1 Cache");

        $this->cache[$key] = serialize($value);
        if (!empty($ttl)) {
            $this->cache["ttl"]["$key"] = $this->addToNow($ttl);
        }

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * Unlock resource
     *
     * @param string $key
     * @return bool
     */
    #[\Override]
    public function delete(string $key): bool
    {
        $key = $this->getKeyFromContainer($key);

        unset($this->cache[$key]);
        unset($this->cache["ttl"]["$key"]);
        return true;
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return true;
    }

    #[\Override]
    public function collectGarbage()
    {
        foreach ($this->cache["ttl"] as $key => $ttl) {
            if (time() >= $ttl) {
                unset($this->cache[$key]);
                unset($this->cache["ttl"]["$key"]);
            }
        }
    }

    #[\Override]
    public function getTtl(string $key): ?int
    {
        $key = $this->getKeyFromContainer($key);
        if (isset($this->cache["ttl"]["$key"])) {
            return $this->cache["ttl"]["$key"];
        }

        return null;
    }
}
