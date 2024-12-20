<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheLockInterface;
use DateInterval;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\InvalidArgumentException;

class FileSystemCacheEngine extends BaseCacheEngine implements CacheLockInterface
{

    protected ?LoggerInterface $logger = null;

    protected ?string $prefix = null;
    protected ?string $path = null;

    public function __construct(string $prefix = 'cache', ?string $path = null, ?LoggerInterface $logger = null, bool $createPath = false)
    {
        $this->prefix = $prefix;
        $this->path = $path ?? sys_get_temp_dir();
        if ($createPath && !file_exists($this->path)) {
            mkdir($this->path, 0777, true);
        }

        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @param string $key The object KEY
     * @param mixed $default IGNORED IN MEMCACHED.
     * @return mixed Description
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ByJG\Cache\Exception\InvalidArgumentException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check if file is Locked
        $fileKey = $this->fixKey($key);
        $lockFile = $fileKey . ".lock";
        if (file_exists($lockFile)) {
            $this->logger->info("[Filesystem cache] Locked! $key. Waiting...");
            $lockTime = filemtime($lockFile);

            while (true) {
                if (!file_exists($lockFile)) {
                    $this->logger->info("[Filesystem cache] Lock released for '$key'");
                    break;
                }
                if (intval(time() - $lockTime) > 20) {  // Wait for 10 seconds
                    $this->logger->info("[Filesystem cache] Gave up to wait unlock. Release lock for '$key'");
                    $this->unlock($key);
                    return $default;
                }
                sleep(1); // 1 second
            }
        }

        // Check if file exists
        if ($this->has($key)) {
            $this->logger->info("[Filesystem cache] Get '$key'");
            return unserialize(file_get_contents($fileKey));
        } else {
            $this->logger->info("[Filesystem cache] Not found '$key'");
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
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $fileKey = $this->fixKey($key);

        $this->logger->info("[Filesystem cache] Set '$key' in FileSystem");

        try {
            if (file_exists($fileKey)) {
                unlink($fileKey);
            }
            if (file_exists("$fileKey.ttl")) {
                unlink("$fileKey.ttl");
            }

            if (is_null($value)) {
                return false;
            }

            if (is_string($value) && (strlen($value) === 0)) {
                touch($fileKey);
            } else {
                file_put_contents($fileKey, serialize($value));
            }

            $validUntil = $this->addToNow($ttl);
            if (!empty($validUntil)) {
                file_put_contents($fileKey . ".ttl", (string)$validUntil);
            }
        } catch (Exception $ex) {
            $this->logger->warning("[Filesystem cache] I could not write to cache on file '" . basename($key) . "'. Switching to nocache=true mode.");
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $this->set($key, null);
        return true;
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock(string $key): void
    {
        $this->logger->info("[Filesystem cache] Lock '$key'");

        $lockFile = $this->fixKey($key) . ".lock";

        try {
            file_put_contents($lockFile, date('c'));
        } catch (Exception $ex) {
            // Ignoring... Set will cause an error
        }
    }

    /**
     * UnLock resource after set it.
     * @param string $key
     */
    public function unlock(string $key): void
    {

        $this->logger->info("[Filesystem cache] Unlock '$key'");

        $lockFile = $this->fixKey($key) . ".lock";

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ByJG\Cache\Exception\InvalidArgumentException
     */
    public function isAvailable(): bool
    {
        return is_writable(dirname($this->fixKey('test')));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ByJG\Cache\Exception\InvalidArgumentException
     */
    protected function fixKey(string $key): string
    {
        $key = $this->getKeyFromContainer($key);

        return $this->path . '/'
            . $this->prefix
            . '-' . preg_replace("/[\/\\\]/", "#", $key)
            . '.cache';
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ByJG\Cache\Exception\InvalidArgumentException
     */
    public function clear(): bool
    {
        $patternKey = $this->fixKey('*');
        $list = glob($patternKey);
        foreach ($list as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        return true;
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ByJG\Cache\Exception\InvalidArgumentException
     */
    public function has(string $key): bool
    {
        $fileKey = $this->fixKey($key);
        if (file_exists($fileKey)) {
            if (file_exists("$fileKey.ttl")) {
                $fileTtl = intval(file_get_contents("$fileKey.ttl"));
            }

            if (!empty($fileTtl) && time() >= $fileTtl) {
                $this->logger->info("[Filesystem cache] File too old. Ignoring '$key'");
                $this->delete($key);

                return false;
            }

            return true;
        }

        return false;
    }
}
