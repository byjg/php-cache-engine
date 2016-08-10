<?php

namespace ByJG\Cache;

use Exception;

class FileSystemCacheEngine implements CacheEngineInterface
{

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        $log = LogHandler::getInstance();

        if ($ttl === false) {
            $log->info("[Filesystem cache] Ignored  $key because TTL=FALSE");
            return null;
        }

        if (CacheContext::getInstance()->getReset()) {
            $log->info("[Filesystem cache] Failed to get $key because RESET=true");
            return null;
        }
        if (CacheContext::getInstance()->getNoCache()) {
            $log->info("[Filesystem cache] Failed to get $key because NOCACHE=true");
            return null;
        }

        // Check if file is Locked
        $fileKey = $this->fixKey($key);
        $lockFile = $fileKey . ".lock";
        if (file_exists($lockFile)) {
            $log->info("[Filesystem cache] Locked! $key. Waiting...");
            $lockTime = filemtime($lockFile);

            while (true) {
                if (!file_exists($lockFile)) {
                    $log->info("[Filesystem cache] Lock released for '$key'");
                    break;
                }
                if (intval(time() - $lockTime) > 20) {  // Wait for 10 seconds
                    $log->info("[Filesystem cache] Gave up to wait unlock. Release lock for '$key'");
                    $this->unlock($key);
                    return null;
                }
                sleep(1); // 1 second
            }
        }

        // Check if file exists
        if (file_exists($fileKey)) {
            $fileAge = filemtime($fileKey);

            if (($ttl > 0) && (intval(time() - $fileAge) > $ttl)) {
                $log->info("[Filesystem cache] File too old. Ignoring '$key'");
                return null;
            } else {
                $log->info("[Filesystem cache] Get '$key'");
                return unserialize(file_get_contents($fileKey));
            }
        } else {
            $log->info("[Filesystem cache] Not found '$key'");
            return null;
        }
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0)
    {
        $log = LogHandler::getInstance();

        $fileKey = $this->fixKey($key);

        if (!CacheContext::getInstance()->getNoCache()) {
            $log->info("[Filesystem cache] Set '$key' in FileSystem");

            try {
                if (file_exists($fileKey)) {
                    unlink($fileKey);
                }

                if (is_null($object)) {
                    return false;
                }

                if (is_string($object) && (strlen($object) === 0)) {
                    touch($fileKey);
                } else {
                    file_put_contents($fileKey, serialize($object));
                }
            } catch (Exception $ex) {
                echo "<br/><b>Warning:</b> I could not write to cache on file '" . basename($key) . "'. Switching to nocache=true mode. <br/>";
            }
        } else {
            $log->info("[Filesystem cache] Not Set '$key' because NOCACHE=true");
        }
        
        return true;
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function release($key)
    {
        $this->set($key, null);
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function append($key, $content, $ttl = 0)
    {
        $log = LogHandler::getInstance();

        $fileKey = $this->fixKey($key);

        if (!CacheContext::getInstance()->getNoCache()) {
            $log->info("[Filesystem cache] Append '$key' in FileSystem");

            try {
                file_put_contents($fileKey, serialize($content), true);
            } catch (Exception $ex) {
                echo "<br/><b>Warning:</b> I could not write to cache on file '" . basename($key) . "'. Switching to nocache=true mode. <br/>";
            }
        } else {
            $log->info("[Filesystem cache] Not Set '$key' because NOCACHE=true");
        }
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key)
    {
        $log = LogHandler::getInstance();
        $log->info("[Filesystem cache] Lock '$key'");

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
    public function unlock($key)
    {
        $log = LogHandler::getInstance();
        $log->info("[Filesystem cache] Unlock '$key'");

        $lockFile = $this->fixKey($key) . ".lock";

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    protected function fixKey($key)
    {
        return sys_get_temp_dir() . '/'
            . (isset($this->configKey) ? $this->configKey : "default")
            . '-' . preg_replace("/[\/\\\]/", "#", $key)
            . '.cache';
    }
}
