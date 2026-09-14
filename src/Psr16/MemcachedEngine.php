<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\CompareAndSwapInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\Exception\StorageErrorException;
use DateInterval;
use Memcached;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MemcachedEngine extends BaseCacheEngine implements AtomicOperationInterface, CompareAndSwapInterface
{
    /**
     * Memcached expires an item immediately when handed a negative expiration. That is the only
     * way to retire a key through cas(), since the extension exposes no compare-and-delete.
     */
    private const EXPIRE_NOW = -1;

    /**
     *
     * @var Memcached|null
     */
    protected Memcached|null $memCached = null;

    protected LoggerInterface|null $logger = null;

    protected ?array $servers = null;

    protected ?array $options = null;

    public function __construct(?array $servers = null, $logger = null, ?array $options = null)
    {
        $this->servers = (array)$servers;
        if (is_null($servers)) {
            $this->servers = [
                '127.0.0.1:11211'
            ];
        }

        $this->logger = $logger instanceof LoggerInterface ? $logger : null;
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }

        $this->options = $options;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    protected function fixKey(string $key): string
    {
        $key = $this->getKeyFromContainer($key);
        $key = preg_replace('/[^A-Za-z0-9_\-]/', '-', $key);
        return "cache-" . $key;
    }

    /**
     * @throws StorageErrorException
     */
    protected function lazyLoadMemCachedServers(): void
    {
        if (is_null($this->memCached)) {
            $this->memCached = new Memcached();

            // Apply options if provided
            if (is_array($this->options)) {
                foreach ($this->options as $opt => $val) {
                    // Accept both numeric keys (constants) and string keys like 'OPT_CONNECT_TIMEOUT'
                    if (is_string($opt) && defined(Memcached::class . '::' . $opt)) {
                        $opt = constant(Memcached::class . '::' . $opt);
                    }
                    if (is_int($opt)) {
                        $this->memCached->setOption($opt, $val);
                    } else {
                        $this->logger->warning("[Memcached] Failed to set option {$opt} with value " . json_encode($val));
                    }
                }
            }

            // Add servers. Accept formats:
            // - ['host:port', ...]
            // - [['host', port], ...]
            foreach ($this->servers as $server) {
                $host = null;
                $port = null;
                if (is_string($server)) {
                    $data = explode(":", $server);
                    $host = $data[0] ?? '127.0.0.1';
                    $port = isset($data[1]) ? intval($data[1]) : 11211;
                } elseif (is_array($server) && isset($server[0])) {
                    $host = (string)$server[0];
                    $port = isset($server[1]) ? intval($server[1]) : 11211;
                }

                if ($host === null || $port === null) {
                    $this->logger->warning("[Memcached] Invalid server configuration skipped: " . json_encode($server));
                    continue; // skip invalid entry
                }

                $this->memCached->addServer($host, $port);

                // Server health check
                $stats = $this->memCached->getStats();
                $key = $host . ':' . $port;
                if (!isset($stats[$key]) || (isset($stats[$key]['pid']) && $stats[$key]['pid'] === -1)) {
                    throw new StorageErrorException("Memcached server {$key} is down");
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
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $this->lazyLoadMemCachedServers();

        $value = $this->memCached->get($this->fixKey($key));
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->info("[Memcached] Cache '$key' missed with status " . $this->memCached->getResultCode());
            return $default;
        }

        return $value;
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
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->lazyLoadMemCachedServers();

        $ttl = $this->convertToSeconds($ttl);

        $this->memCached->set($this->fixKey($key), $value, is_null($ttl) ? 0 : $ttl);
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
    #[\Override]
    public function delete(string $key): bool
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->delete($this->fixKey($key));
        return true;
    }

    #[\Override]
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
    #[\Override]
    public function clear(): bool
    {
        $this->lazyLoadMemCachedServers();
        return $this->memCached->flush();
    }

    /**
     * @param string $key
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws StorageErrorException
     */
    #[\Override]
    public function has(string $key): bool
    {
        $this->lazyLoadMemCachedServers();

        $this->memCached->get($this->fixKey($key));
        return ($this->memCached->getResultCode() === Memcached::RES_SUCCESS);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws StorageErrorException
     */
    #[\Override]
    public function increment(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        $this->lazyLoadMemCachedServers();

        $fixKey = $this->fixKey($key);
        $this->seed($fixKey, 0, $ttl);

        $result = $this->memCached->increment($fixKey, $value);
        $this->logger->info("[Memcached] Increment '$key' result " . $this->memCached->getResultCode());
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->error("[Memcached] Increment '$key' failed with status " . $this->memCached->getResultCode());
        }

        return $result;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws StorageErrorException
     */
    #[\Override]
    public function decrement(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        $this->lazyLoadMemCachedServers();

        $fixKey = $this->fixKey($key);
        $this->seed($fixKey, 0, $ttl);

        $result = $this->memCached->decrement($fixKey, $value);
        $this->logger->info("[Memcached] Decrement '$key' result " . $this->memCached->getResultCode());
        if ($this->memCached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->logger->error("[Memcached] Decrement '$key' failed with status " . $this->memCached->getResultCode());
        }

        return $result;
    }

    /**
     * Create the key only if it is absent, so a counter can be started without a race.
     *
     * increment()/decrement() fail on a missing key, which forces every caller to initialise it
     * first. Doing that with get()-then-set() is what used to break: two processes could both read
     * the key as absent, and the slower one's set(0) would land AFTER the faster one's increment,
     * resetting the counter and handing out the same value twice. add() is resolved server-side in
     * a single step, so exactly one caller creates the key and everybody else silently moves on.
     */
    private function seed(string $fixKey, mixed $initial, DateInterval|int|null $ttl): void
    {
        $ttl = $this->convertToSeconds($ttl);

        $this->memCached->add($fixKey, $initial, is_null($ttl) ? 0 : $ttl);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws StorageErrorException
     */
    #[\Override]
    public function add(string $key, $value, DateInterval|int|null $ttl = null): array
    {
        $this->lazyLoadMemCachedServers();

        $ttl = $this->convertToSeconds($ttl);
        $expiration = is_null($ttl) ? 0 : $ttl;
        $fixKey = $this->fixKey($key);

        while (true) {
            $data = $this->memCached->get($fixKey, null, Memcached::GET_EXTENDED);

            // Absent: claim it with add(), which only succeeds for the first caller to get there.
            // Whoever loses simply goes round again and finds the list the winner created.
            if ($data === false) {
                if ($this->memCached->add($fixKey, [$value], $expiration)) {
                    return [$value];
                }
                continue;
            }

            $currentValue = $data['value'];
            if (!is_array($currentValue)) {
                $currentValue = [$currentValue];
            }
            $currentValue[] = $value;

            // cas() writes only while the item is untouched since the read above; if another
            // append slipped in, the token is stale, the write is refused and we retry on top of it.
            if ($this->memCached->cas($data['cas'], $fixKey, $currentValue, $expiration)) {
                return $currentValue;
            }
        }
    }

    #[\Override]
    public function setIfAbsent(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->lazyLoadMemCachedServers();

        $ttl = $this->convertToSeconds($ttl);

        // Memcached's own add() is the primitive this whole interface is named after.
        return $this->memCached->add($this->fixKey($key), $value, is_null($ttl) ? 0 : $ttl);
    }

    #[\Override]
    public function deleteIfEquals(string $key, mixed $value): bool
    {
        // No compare-and-delete exists, so retire the item by expiring it in the same cas() that
        // proves we are still looking at the value we expect.
        return $this->casIfEquals($key, $value, self::EXPIRE_NOW);
    }

    #[\Override]
    public function expireIfEquals(string $key, mixed $value, DateInterval|int|null $ttl): bool
    {
        $ttl = $this->convertToSeconds($ttl);

        return $this->casIfEquals($key, $value, is_null($ttl) ? 0 : $ttl);
    }

    /**
     * Rewrite the item with a new expiration, but only while it still holds $value.
     *
     * The cas token read here is invalidated server-side by any competing write, so a caller whose
     * item was replaced between the get() and the cas() is refused rather than silently clobbering
     * the new owner's value.
     */
    private function casIfEquals(string $key, mixed $value, int $expiration): bool
    {
        $this->lazyLoadMemCachedServers();

        $fixKey = $this->fixKey($key);
        $data = $this->memCached->get($fixKey, null, Memcached::GET_EXTENDED);

        if ($data === false || $data['value'] != $value) {
            return false;
        }

        return $this->memCached->cas($data['cas'], $fixKey, $data['value'], $expiration);
    }
}
