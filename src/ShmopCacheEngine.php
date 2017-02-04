<?php

namespace ByJG\Cache;

use InvalidArgumentException;

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
class ShmopCacheEngine implements CacheEngineInterface
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
        return sys_get_temp_dir() . '/' . sha1($key);
    }
    
    protected function getMaxSize()
    {
        return $this->config['max-size'];
    }

    protected function getDefaultPermission()
    {
        return $this->config['default-permission'];
    }

    protected function getKeyId($key)
    {
        $file = $this->getFTok($key);
        if (!file_exists($file)) {
            touch($file);
        }
        return ftok($file, 'j');
    }

    /**
     * @param string $key The object KEY
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return object The Object
     */
    public function get($key, $ttl = 0)
    {
        

        if ($ttl === false) {
            $this->logger->info("[Shmop Cache] Ignored  $key because TTL=FALSE");
            return null;
        }

        $fileKey = $this->getKeyId($key);

        // Opened
        $shm_id = @shmop_open($fileKey, "a", 0, 0);
        if (!$shm_id) {
            $this->logger->info("[Shmop Cache] '$key' not exists");
            return null;
        }

        $fileAge = filemtime($this->getFTok($key));

        // Check
        if (($ttl > 0) && (intval(time() - $fileAge) > $ttl)) {
            $this->logger->info("[Shmop Cache] File too old. Ignoring '$key'");

            // Close old descriptor
            shmop_close($shm_id);

            // delete old memory segment
            $shm_id = shmop_open($fileKey, "w", $this->getDefaultPermission(), $this->getMaxSize());
            shmop_delete($shm_id);
            shmop_close($shm_id);
            return null;
        }

        $this->logger->info("[Shmop Cache] Get '$key'");

        $serialized = shmop_read($shm_id, 0, shmop_size($shm_id));
        shmop_close($shm_id);

        return unserialize($serialized);
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return bool If the object is successfully posted
     * @throws \Exception
     */
    public function set($key, $object, $ttl = 0)
    {
        

        $this->logger->info("[Shmop Cache] set '$key'");

        $this->release($key);

        $serialized = serialize($object);
        $size = strlen($serialized);

        if ($size > $this->getMaxSize()) {
            throw new \Exception('Object is greater than the max size allowed: ' . $this->getMaxSize());
        }

        $shmKey = $this->getKeyId($key);
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
    }

    /**
     * Append only will work with strings.
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    public function append($key, $str)
    {
        $old = $this->get($key);
        if ($old === false) {
            $this->set($key, $str);
        } else {
            $oldUn = unserialize($old);
            if (is_string($oldUn)) {
                $this->release($key);
                $this->set($key, $oldUn . $str);
            } else {
                throw new InvalidArgumentException('Only is possible append string types');
            }
        }
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key)
    {
        
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function unlock($key)
    {
        
    }

    /**
     * Release the object
     * @param string $key
     */
    public function release($key)
    {
        

        $this->logger->info("[Shmop Cache] release '$key'");

        if ($this->get($key) === false) {
            $this->logger->info("[Shmop Cache] release '$key' does not exists.");
            return;
        }

        $filekey = $this->getKeyId($key);
        $shm_id = shmop_open($filekey, "w", 0, 0);

        $file = $this->getFTok($key);
        if (file_exists($file)) {
            unlink($file);
        }

        if ($shm_id) {
            shmop_delete($shm_id);
            shmop_close($shm_id);

            $this->logger->info("[Shmop Cache] release '$key' confirmed.");
        }
    }
}
