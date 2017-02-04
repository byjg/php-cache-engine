<?php

namespace ByJG\Cache\Engine;

use ByJG\Cache\CacheEngineInterface;
use Exception;
use Psr\Log\NullLogger;

class FileSystemCacheEngine implements CacheEngineInterface
{

    protected $logger = null;

    protected $prefix = null;

    public function __construct($prefix = 'cache', $logger = null)
    {
        $this->prefix = $prefix;

        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        if ($ttl === false) {
            $this->logger->info("[Filesystem cache] Ignored  $key because TTL=FALSE");
            return null;
        }

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
                    return null;
                }
                sleep(1); // 1 second
            }
        }

        // Check if file exists
        if (file_exists($fileKey)) {
            $fileAge = filemtime($fileKey);

            if (($ttl > 0) && (intval(time() - $fileAge) > $ttl)) {
                $this->logger->info("[Filesystem cache] File too old. Ignoring '$key'");
                return null;
            } else {
                $this->logger->info("[Filesystem cache] Get '$key'");
                return unserialize(file_get_contents($fileKey));
            }
        } else {
            $this->logger->info("[Filesystem cache] Not found '$key'");
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
        $fileKey = $this->fixKey($key);

        $this->logger->info("[Filesystem cache] Set '$key' in FileSystem");

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
     * @param string $content The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function append($key, $content, $ttl = 0)
    {
        $fileKey = $this->fixKey($key);

        $this->logger->info("[Filesystem cache] Append '$key' in FileSystem");

        try {
            file_put_contents($fileKey, serialize($content), true);
        } catch (Exception $ex) {
            echo "<br/><b>Warning:</b> I could not write to cache on file '" . basename($key) . "'. Switching to nocache=true mode. <br/>";
        }
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key)
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
    public function unlock($key)
    {
        
        $this->logger->info("[Filesystem cache] Unlock '$key'");

        $lockFile = $this->fixKey($key) . ".lock";

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    public function isAvailable()
    {
        return is_writable(dirname($this->fixKey('test')));
    }

    protected function fixKey($key)
    {
        return sys_get_temp_dir() . '/'
            . $this->prefix
            . '-' . preg_replace("/[\/\\\]/", "#", $key)
            . '.cache';
    }
}
