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

    private $config = null;

    protected function getFTok($key)
    {
        return sys_get_temp_dir() . '/' . sha1($key);
    }
    
    protected function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = CacheContext::getInstance()->getMemcachedConfig(isset($this->configKey) ? $this->configKey : 'default');
        }
        return $this->config;
    }

    protected function getMaxSize()
    {
        $config = $this->getConfig();
        return isset($config['max-size']) ? $config['max-size'] : 524288;
    }

    protected function getDefaultPermission()
    {
        $config = $this->getConfig();
        return isset($config['default-permission']) ? $config['default-permission'] : '0700';
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
        $log = LogHandler::getInstance();

        if ($ttl === false) {
            $log->info("[Shmop Cache] Ignored  $key because TTL=FALSE");
            return false;
        }

        if (CacheContext::getInstance()->getReset()) {
            $log->info("[Shmop Cache] Failed to get $key because RESET=true");
            return false;
        }
        if (CacheContext::getInstance()->getNoCache()) {
            $log->info("[Shmop Cache] Failed to get $key because NOCACHE=true");
            return false;
        }

        $fileKey = $this->getKeyId($key);

        // Opened
        $shm_id = @shmop_open($fileKey, "a", 0, 0);
        if (!$shm_id) {
            $log->info("[Shmop Cache] '$key' not exists");
            return false;
        }

        $fileAge = filemtime($this->getFTok($key));

        // Check
        if (($ttl > 0) && (intval(time() - $fileAge) > $ttl)) {
            $log->info("[Shmop Cache] File too old. Ignoring '$key'");

            // Close old descriptor
            shmop_close($shm_id);

            // delete old memory segment
            $shm_id = shmop_open($fileKey, "w", $this->getDefaultPermission(), $this->getMaxSize());
            shmop_delete($shm_id);
            shmop_close($shm_id);
            return false;
        }

        $log->info("[Shmop Cache] Get '$key'");

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
        $log = LogHandler::getInstance();

        $log->info("[Shmop Cache] set '$key'");

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
        $log->info("[Shmop Cache] set '$key' confirmed write $shm_bytes_written bytes of $size bytes");
        if ($shm_bytes_written != $size) {
            $log->warning("Couldn't write the entire length of data");
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
        $log = LogHandler::getInstance();

        $log->info("[Shmop Cache] release '$key'");

        if ($this->get($key) === false) {
            $log->info("[Shmop Cache] release '$key' does not exists.");
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

            $log->info("[Shmop Cache] release '$key' confirmed.");
        }
    }
}
