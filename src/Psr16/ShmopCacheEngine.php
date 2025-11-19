<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\Exception\StorageErrorException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Caching based on Unix Share Memory
 *
 * # ipcs -m
 * List all segments used
 *
 * # ipcs -lm
 * ------ Shared Memory Limits --------
 * max number of segments = 4096       <--- this is SHMMNI
 * max seg size (kbytes) = 67108864    <--- this is SHMMAX
 * max total shared memory (kbytes) = 17179869184<- this is SHMALL
 * min seg size (bytes) = 1
 *
 *
 * @deprecated Use TmpfsCacheEngine instead
 */
class ShmopCacheEngine extends BaseCacheEngine
{
    protected LoggerInterface|null $logger = null;

    protected array $config = [];

    public function __construct(array $config = [], ?LoggerInterface $logger = null)
    {
        $this->config = $config;

        if (!isset($this->config['max-size'])) {
            $this->config['max-size'] = 524288;
        }
        if (!isset($this->config['default-permission'])) {
            $this->config['default-permission'] = '0700';
        }

        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    protected function getFilenameToken(string $key): string
    {
        $key = $this->getKeyFromContainer($key);
        return sys_get_temp_dir() . '/shmop-' . sha1($key) . '.cache';
    }
    
    protected function getMaxSize()
    {
        return $this->config['max-size'];
    }

    protected function getDefaultPermission()
    {
        return $this->config['default-permission'];
    }

    protected function getFTok(string $file): int
    {
        if (!file_exists($file)) {
            touch($file);
        }
        return ftok($file, 'j');
    }

    /**
     * @param string $key The object KEY
     * @param mixed $default The time to live in seconds of the object. Depends on implementation.
     * @return mixed The Object
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
       if ($default === false) {
            $this->logger->info("[Shmop Cache] Ignored  $key because TTL=FALSE");
            return $default;
        }

        $file = $this->getFilenameToken($key);
        $fileKey = $this->getFTok($file);

        // Opened
        $shm_id = @shmop_open($fileKey, "a", 0, 0);
        if (!$shm_id) {
            $this->logger->info("[Shmop Cache] '$key' not exists");
            return $default;
        }

        if (!$this->isValidAge($file)) {
            return $default;
        }

        $this->logger->info("[Shmop Cache] Get '$key'");

        $serialized = shmop_read($shm_id, 0, shmop_size($shm_id));
        // shmop_close($shm_id);

        return unserialize($serialized);
    }

    protected function isValidAge(string $file): bool
    {
        if (file_exists("$file.ttl")) {
            $fileTtl = intval(file_get_contents("$file.ttl"));
        }

        if (!empty($fileTtl) && time() >= $fileTtl) {
            $this->logger->info("[Shmop Cache] File too old. Ignoring");
            $this->deleteFromFilenameToken($file);
            return false;
        }

        return true;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws StorageErrorException
     */
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->logger->info("[Shmop Cache] set '$key'");

        $this->delete($key);

        $serialized = serialize($value);
        $size = strlen($serialized);

        if ($size > $this->getMaxSize()) {
            throw new StorageErrorException('Object is greater than the max size allowed: ' . $this->getMaxSize());
        }

        $file = $this->getFilenameToken($key);
        $shmKey = $this->getFTok($file);
        $shm_id = shmop_open($shmKey, "c", 0777, $size);
        if (!$shm_id) {
            $message = "Couldn't create shared memory segment";
            $lastError = error_get_last();
            if (isset($lastError['message'])) {
                $message = $lastError['message'];
            }
            throw new StorageErrorException($message);
        }

        $shm_bytes_written = shmop_write($shm_id, $serialized, 0);
        $this->logger->info("[Shmop Cache] set '$key' confirmed write $shm_bytes_written bytes of $size bytes");
        if ($shm_bytes_written != $size) {
            $this->logger->warning("Couldn't write the entire length of data");
        }
        // shmop_close($shm_id);

        $validUntil = $this->addToNow($ttl);
        if (!empty($validUntil)) {
            file_put_contents("$file.ttl", (string)$validUntil);
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function delete(string $key): bool
    {
        $this->logger->info("[Shmop Cache] release '$key'");

        if ($this->get($key) === false) {
            $this->logger->info("[Shmop Cache] release '$key' does not exists.");
            return false;
        }

        $file = $this->getFilenameToken($key);
        $this->deleteFromFilenameToken($file);
        return true;
    }

    private function deleteFromFilenameToken(string $file): void
    {
        $filekey = $this->getFTok($file);
        $shm_id = @shmop_open($filekey, "w", 0, 0);

        if (file_exists($file)) {
            unlink($file);
        }

        if (file_exists("$file.ttl")) {
            unlink("$file.ttl");
        }

        if ($shm_id) {
            shmop_delete($shm_id);
            // shmop_close($shm_id);

            $this->logger->info("[Shmop Cache] release confirmed.");
        }
    }

    #[\Override]
    public function clear(): bool
    {
        $patternKey = sys_get_temp_dir() . '/shmop-*.cache';
        $list = glob($patternKey);
        foreach ($list as $file) {
            $this->deleteFromFilenameToken($file);
        }
        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function has(string $key): bool
    {
        $file = $this->getFilenameToken($key);
        $fileKey = $this->getFTok($file);

        // Opened
        $shm_id = @shmop_open($fileKey, "a", 0, 0);

        $exists = !(!$shm_id);

        if ($exists) {
            // shmop_close($shm_id);
            return $this->isValidAge($file);
        }

        return false;
    }


    #[\Override]
    public function isAvailable(): bool
    {
        return function_exists('shmop_open');
    }
}
