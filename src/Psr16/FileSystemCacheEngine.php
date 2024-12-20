<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\GarbageCollectorInterface;
use Closure;
use DateInterval;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FileSystemCacheEngine extends BaseCacheEngine implements GarbageCollectorInterface, AtomicOperationInterface
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

        // Check if file exists
        if ($this->has($key)) {
            $this->logger->info("[Filesystem cache] Get '$key'");
            return $this->getContents($fileKey, $default);
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
            if (is_string($value) && (strlen($value) === 0)) {
                touch($fileKey);
            } else {
                return $this->putContents($fileKey, $value, $this->addToNow($ttl));
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
        $fileTtl = null;
        if (file_exists($fileKey)) {
            if (file_exists("$fileKey.ttl")) {
                $fileTtl = intval($this->getContents("$fileKey.ttl"));
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

    protected function getContents(string $fileKey, mixed $default = null): mixed
    {
        if (!file_exists($fileKey)) {
            return $default;
        }

        $fo = fopen($fileKey, 'r');
        $waitIfLocked = 1;
        $lock = flock($fo, LOCK_EX, $waitIfLocked);
        try {
            $content = unserialize(file_get_contents($fileKey));
        } finally {
            flock($fo, LOCK_UN);
            fclose($fo);
        }

        return $content;
    }

    protected function putContents(string $fileKey, mixed $value, ?int $ttl, ?Closure $operation = null): mixed
    {
        $returnValue = true;

        if (file_exists("$fileKey.ttl")) {
            unlink("$fileKey.ttl");
        }

        if (is_null($value)) {
            if (file_exists($fileKey)) {
                unlink($fileKey);
            }
            return false;
        }

        $fo = fopen($fileKey, 'a+');
        $waitIfLocked = 1;
        $lock = flock($fo, LOCK_EX, $waitIfLocked);
        try {
            if (!is_null($operation)) {
                if (!file_exists($fileKey)) {
                    $currentValue = 0;
                } else {
                    $content = file_get_contents($fileKey);
                    $currentValue = !empty($content) ? unserialize($content) : $content;
                }
                $value = $returnValue = $operation($currentValue, $value);
            }
            file_put_contents($fileKey, serialize($value));
            if (!is_null($ttl)) {
                file_put_contents("$fileKey.ttl", serialize($ttl));
            }
        } finally {
            flock($fo, LOCK_UN);
            fclose($fo);
        }

        return $returnValue;
    }

    public function collectGarbage()
    {
        $patternKey = $this->fixKey('*');
        $list = glob("$patternKey.ttl");
        foreach ($list as $file) {
            $fileTtl = intval($this->getContents($file));
            if (time() >= $fileTtl) {
                $fileContent = str_replace('.ttl', '', $file);
                if (file_exists($fileContent)) {
                    unlink($fileContent);
                }
                unlink($file);
            }
        }
        return true;
    }


    public function getTtl(string $key): ?int
    {
        $fileKey = $this->fixKey($key);
        if (file_exists("$fileKey.ttl")) {
            return intval($this->getContents("$fileKey.ttl"));
        }
        return null;
    }

    public function increment(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        return $this->putContents($this->fixKey($key), $value, $ttl, function ($currentValue, $value) {
            return intval($currentValue) + $value;
        });
    }

    public function decrement(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        return $this->putContents($this->fixKey($key), $value, $ttl, function ($currentValue, $value) {
            return intval($currentValue) - $value;
        });
    }

    public function add(string $key, $value, DateInterval|int|null $ttl = null): array
    {
        return $this->putContents($this->fixKey($key), $value, $ttl, function ($currentValue, $value) {
            if (empty($currentValue)) {
                return [$value];
            }
            if (!is_array($currentValue)) {
                return [$currentValue, $value];
            }
            $currentValue[] = $value;
            return $currentValue;
        });
    }
}
