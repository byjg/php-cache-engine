<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\Exception\StorageErrorException;
use DateInterval;
use Memcached;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MemcachedEngine extends BaseCacheEngine
{

    /**
     *
     * @var Memcached|null
     */
    protected Memcached|null $memCached = null;

    protected LoggerInterface|null $logger = null;

    protected ?array $servers = null;

    public function __construct(?array $servers = null, $logger = null)
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

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    protected function fixKey(string $key): string
    {
        $key = $this->getKeyFromContainer($key);
        return "cache-" . $key;
    }

    /**
     * @throws StorageErrorException
     */
    protected function lazyLoadMemCachedServers(): void
    {
        if (is_null($this->memCached)) {
            $this->memCached = new Memcached();
            foreach ($this->servers as $server) {
                $data = explode(":", $server);
                $this->memCached->addServer($data[0], intval($data[1]));

                $stats = $this->memCached->getStats();
                if (!isset($stats[$server]) || $stats[$server]['pid'] === -1) {
                    throw new StorageErrorException("Memcached server $server is down");
                }
            }
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws StorageErrorException
     */
    public function get(string $key, mixed $default = null): mixed
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
     * @param mixed $value The object to be cached
     * @param DateInterval|int|null $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws StorageErrorException
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->lazyLoadMemCachedServers();

        $ttl = $this->convertToSeconds($ttl);

        $this->memCached->set($this->fixKey($key), serialize($value), is_null($ttl) ? 0 : $ttl);
        $this->logger->info("[Memcached] Set '$key' result " . $this->memCached->getResultCode());
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->error("[Memcached] Set '$key' failed with status " . $this->memCached->getResultCode());
        }

        return $this->memCached->getResultCode() === Memcached::RES_SUCCESS;
    }

    /**
     * @param string $key
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws StorageErrorException
     */
    public function delete(string $key): bool
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->delete($this->fixKey($key));
        return true;
    }

    public function isAvailable(): bool
    {
        if (!class_exists('\Memcached')) {
            return false;
        }

        try {
            $this->lazyLoadMemCachedServers();
            return true;
        } catch (StorageErrorException $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws StorageErrorException
     */
    public function clear(): bool
    {
        $this->lazyLoadMemCachedServers();
        $result = $this->memCached->flush();
        return $result;
    }

    /**
     * @param string $key
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws StorageErrorException
     */
    public function has(string $key): bool
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->get($this->fixKey($key));
        return ($this->memCached->getResultCode() === Memcached::RES_SUCCESS);
    }
}
