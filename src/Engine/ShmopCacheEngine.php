<?php

namespace ByJG\Cache\Engine;

use ByJG\Cache\CacheEngineInterface;
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
 */
class ShmopCacheEngine extends BaseCacheEngine
{
    protected $logger = null;

    protected $config = [];

    public function __construct($config = [], $logger = null)
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

    protected function getFTok($key)
    {
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

    protected function getKeyId($file)
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
     */
    public function get($key, $default = null)
    {
       if ($default === false) {
            $this->logger->info("[Shmop Cache] Ignored  $key because TTL=FALSE");
            return $default;
        }

        $file = $this->getFTok($key);
        $fileKey = $this->getKeyId($file);

        // Opened
        $shm_id = @shmop_open($fileKey, "a", 0, 0);
        if (!$shm_id) {
            $this->logger->info("[Shmop Cache] '$key' not exists");
            return $default;
        }

        // $fileAge = filemtime($this->getFTok($key));

        // Check @todo TTL
        // if (($default > 0) && (intval(time() - $fileAge) > $default)) {
        //     $this->logger->info("[Shmop Cache] File too old. Ignoring '$key'");
        //
        //     // Close old descriptor
        //     shmop_close($shm_id);
        //
        //     // delete old memory segment
        //     $shm_id = shmop_open($fileKey, "w", $this->getDefaultPermission(), $this->getMaxSize());
        //     shmop_delete($shm_id);
        //     shmop_close($shm_id);
        //     return $default;
        // }

        $this->logger->info("[Shmop Cache] Get '$key'");

        $serialized = shmop_read($shm_id, 0, shmop_size($shm_id));
        shmop_close($shm_id);

        return unserialize($serialized);
    }

    /**
     * @param string $key The object Key
     * @param object $value The object to be cached
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return bool If the object is successfully posted
     * @throws \Exception
     */
    public function set($key, $value, $ttl = 0)
    {
        $this->logger->info("[Shmop Cache] set '$key'");

        $this->delete($key);

        $serialized = serialize($value);
        $size = strlen($serialized);

        if ($size > $this->getMaxSize()) {
            throw new \Exception('Object is greater than the max size allowed: ' . $this->getMaxSize());
        }

        $file = $this->getFTok($key);
        $shmKey = $this->getKeyId($file);
        $shm_id = shmop_open($shmKey, "c", 0777, $size);
        if (!$shm_id) {
            $message = "Couldn't create shared memory segment";
            $lastError = error_get_last();
            if (isset($lastError['message'])) {
                $message = $lastError['message'];
            }
            throw new \Exception($message);
        }

        $shm_bytes_written = shmop_write($shm_id, $serialized, 0);
        $this->logger->info("[Shmop Cache] set '$key' confirmed write $shm_bytes_written bytes of $size bytes");
        if ($shm_bytes_written != $size) {
            $this->logger->warning("Couldn't write the entire length of data");
        }
        shmop_close($shm_id);

        return true;
    }

    /**
     * Release the object
     * @param string $key
     */
    public function delete($key)
    {
        $this->logger->info("[Shmop Cache] release '$key'");

        if ($this->get($key) === false) {
            $this->logger->info("[Shmop Cache] release '$key' does not exists.");
            return;
        }

        $file = $this->getFTok($key);
        $this->deleteFromFTok($file);
    }

    private function deleteFromFTok($file)
    {
        $filekey = $this->getKeyId($file);
        $shm_id = @shmop_open($filekey, "w", 0, 0);

        if (file_exists($file)) {
            unlink($file);
        }

        if ($shm_id) {
            shmop_delete($shm_id);
            shmop_close($shm_id);

            $this->logger->info("[Shmop Cache] release confirmed.");
        }
    }

    public function clear()
    {
        $patternKey = sys_get_temp_dir() . '/shmop-*.cache';
        $list = glob($patternKey);
        foreach ($list as $file) {
            $this->deleteFromFTok($file);
        }
    }

    public function has($key)
    {
        $file = $this->getFTok($key);
        $fileKey = $this->getKeyId($file);

        // Opened
        $shm_id = @shmop_open($fileKey, "a", 0, 0);

        $exists = !(!$shm_id);

        if ($exists) {
            shmop_close($shm_id);
        }

        return $exists;
    }


    public function isAvailable()
    {
        return function_exists('shmop_open');
    }
}
