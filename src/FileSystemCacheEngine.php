<?php

namespace ByJG\Cache;

use Exception;

class  FileSystemCacheEngine implements CacheEngineInterface
{
	use \ByJG\DesignPattern\Singleton;

	protected function __construct()
	{
        // Nothing here
	}

	/**
	 * @param string $key The object KEY
	 * @param int $ttl IGNORED IN MEMCACHED.
	 * @return object Description
	 */
	public function get($key, $ttl = 0)
	{
		$log = LogHandler::getInstance();

		if ($ttl === false)
		{
			$log->info("[Cache] Ignored  $key because TTL=FALSE");
			return false;
		}

		if (CacheContext::getInstance()->getReset())
		{
			$log->info("[Cache] Failed to get $key because RESET=true");
			return false;
		}
		if (CacheContext::getInstance()->getNoCache())
		{
			$log->info("[Cache] Failed to get $key because NOCACHE=true");
			return false;
		}

		// Check if file is Locked
		$fileKey = $this->fixKey($key);
		$lockFile = $fileKey . ".lock";
		if (file_exists($lockFile))
		{
			$log->info("[Cache] Locked! $key. Waiting...");
			$lockTime = filemtime($lockFile);

			while(true)
			{
				if (!file_exists($lockFile))
				{
					$log->info("[Cache] Lock released for '$key'");
					break;
				}
				if (intval(time() - $lockTime) > 20)  // Wait for 10 seconds
				{
					$log->info("[Cache] Gave up to wait unlock. Release lock for '$key'");
					$this->unlock($key);
					return false;
				}
				sleep(1); // 1 second
			}
		}

		// Check if file exists
		if (file_exists($fileKey))
		{
			$fileAge = filemtime($fileKey);

			if (($ttl > 0) && (intval(time() - $fileAge) > $ttl))
			{
				$log->info("[Cache] File too old. Ignoring '$key'");
				return false;
			}
			else
			{
				$log->info("[Cache] Get '$key'");
				return unserialize(file_get_contents($fileKey));
			}
		}
		else
		{
			$log->info("[Cache] Not found '$key'");
			return false;
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

		if (!CacheContext::getInstance()->getNoCache())
		{
			$log->info("[Cache] Set '$key' in FileSystem");

			try
			{
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
            }
			catch (Exception $ex)
			{
				echo "<br/><b>Warning:</b> I could not write to cache on file '" . basename($key) . "'. Switching to nocache=true mode. <br/>";
			}
		}
		else
		{
			$log->info("[Cache] Not Set '$key' because NOCACHE=true");
		}
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

		if (!CacheContext::getInstance()->getNoCache())
		{
			$log->info("[Cache] Append '$key' in FileSystem");

			try
			{
				file_put_contents($fileKey, serialize($content), true);
			}
			catch (Exception $ex)
			{
				echo "<br/><b>Warning:</b> I could not write to cache on file '" . basename($key) . "'. Switching to nocache=true mode. <br/>";
			}
		}
		else
		{
			$log->info("[Cache] Not Set '$key' because NOCACHE=true");
		}
	}

	/**
	 * Lock resource before set it.
	 * @param string $key
	 */
	public function lock($key)
	{
		$log = LogHandler::getInstance();
		$log->info("[Cache] Lock '$key'");

		$lockFile = $this->fixKey($key) . ".lock";

		try
		{
			file_put_contents($lockFile, date('c'));
		}
		catch (Exception $ex)
		{
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
		$log->info("[Cache] Unlock '$key'");

		$lockFile = $this->fixKey($key) . ".lock";

		if (file_exists($lockFile)) {
			unlink($lockFile);
        }
	}

	protected function fixKey($key)
	{
		return sys_get_temp_dir() . '/' . preg_replace("/[\/\\\]/", "#", $key) . '.cache';
	}
}
