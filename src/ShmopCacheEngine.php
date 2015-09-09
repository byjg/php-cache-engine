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

    const DEFAULT_PERMISSION = "0700";
    const MAX_SIZE = 524288;

    protected function getFTok($key)
    {
        return sys_get_temp_dir() . '/' . sha1($key);
    }

    protected function getKeyId($key)
    {
        $file = $this->getFTok($key);
        if (!file_exists($file)) {
            touch($file);
        }
        return ftok($file, 'x');
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
            $log->info("[Cache] Ignored  $key because TTL=FALSE");
            return false;
        }

        if (CacheContext::getInstance()->getReset()) {
            $log->info("[Cache] Failed to get $key because RESET=true");
            return false;
        }
        if (CacheContext::getInstance()->getNoCache()) {
            $log->info("[Cache] Failed to get $key because NOCACHE=true");
            return false;
        }

        $fileKey = $this->getKeyId($key);

        // Opened
        $shm_id = shmop_open($fileKey, "a", self::DEFAULT_PERMISSION, self::MAX_SIZE);
        if (!$shm_id) {
            return false;
        }

        $fileAge = filemtime($this->getFTok($key));

        // Check
        if (($ttl > 0) && (intval(time() - $fileAge) > $ttl)) {
            $log->info("[Cache] File too old. Ignoring '$key'");

            shmop_delete($shm_id);
            shmop_close($shm_id);
            return false;
        } else {
            $log->info("[Cache] Get '$key'");

            $serialized = shmop_read($shm_id, 0, shmop_size($shm_id));
            shmop_close($shm_id);

            return unserialize($serialized);
        }
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0)
    {
        $this->release($key);

        $serialized = serialize($object);
        $size = strlen($serialized);

        $shm_id = shmop_open($this->getKeyId($key), "c", self::DEFAULT_PERMISSION, $size);
        if (!$shm_id) {
            throw new \Exception("Couldn't create shared memory segment");
        }
        $shm_bytes_written = shmop_write($shm_id, $serialized, 0);
        if ($shm_bytes_written != $size) {
            warn("Couldn't write the entire length of data");
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
        $shm_id = shmop_open($this->getKeyId($key), "a", self::DEFAULT_PERMISSION, self::MAX_SIZE);

        $file = $this->getFTok($key);
        if (!file_exists($file)) {
            unlink($file);
        }

        if (!$shm_id) {
            return null;
        }

        shmop_delete($shm_id);
        shmop_close($shm_id);
    }
}
