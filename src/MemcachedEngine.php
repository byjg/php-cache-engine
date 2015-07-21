<?php
/*
*=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
*  Copyright:
*
*  XMLNuke: A XML site content management.
*
*  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
*
*  This file is part of XMLNuke project. Visit http://www.xmlnuke.com
*  for more information.
*
*  This program is free software; you can redistribute it and/or
*  modify it under the terms of the GNU General Public License
*  as published by the Free Software Foundation; either version 2
*  of the License, or (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*
*=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
*/


namespace ByJG\Cache;

use InvalidArgumentException;
use Memcached;

class MemcachedEngine implements ICacheEngine
{
	use \ByJG\DesignPattern\Singleton;

	/**
	 *
	 * @var Memcached
	 */
	protected $_memCached = null;

	protected function __construct()
	{
        $config = HttpContext::getInstance()->getMemcachedConfig();

		if (empty($config) || !isset($config['servers'])) {
			throw new InvalidArgumentException("You have to configure the memcached servers in the file 'config/cacheconfig.php'");
		}

        $servers = $config['servers'];

        $this->_memCached = new Memcached();
        foreach ($servers as $server)
        {
            $data = explode(":", $server);
            $this->_memCached->addServer($data[0], $data[1]);

            $stats = $this->_memCached->getStats();
            if (!isset($stats[$server]) || $stats[$server]['pid'] === -1)
            {
                throw new \Exception("Memcached server $server is down");
            }
        }
	}

	/**
	 * @param string $key The object KEY
	 * @param int $ttl IGNORED IN MEMCACHED.
	 * @return object Description
	 */
	public function get($key, $ttl = 0)
	{
		$log = LogHandler::getInstance();
		if (HttpContext::getInstance()->getReset())
		{
			$log->info("[Cache] Get $key failed because RESET=true");
			return false;
		}

		if (HttpContext::getInstance()->getNoCache())
		{
			$log->info("[Cache] Failed to get $key because NOCACHE=true");
			return false;
		}

		$value = $this->_memCached->get($key);
		if ($this->_memCached->getResultCode() !== Memcached::RES_SUCCESS)
		{
			$log->info("[Cache] Cache '$key' missed with status " . $this->_memCached->getResultCode());
			return false;
		}

		return $value;
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

		if (!HttpContext::getInstance()->getNoCache())
		{
            $this->_memCached->set($key, $object, $ttl);
			$log->info("[Cache] Set '$key' result " . $this->_memCached->getResultCode());
            if ($this->_memCached->getResultCode() !== Memcached::RES_SUCCESS)
            {
                $log->error("[Cache] Set '$key' failed with status " . $this->_memCached->getResultCode());
            }

			return $this->_memCached->getResultCode() === Memcached::RES_SUCCESS;
		}
		else
		{
			$log->info("[Cache] Not Set '$key' because NOCACHE=true");
			return true;
		}
	}

	/**
	 * Unlock resource
	 * @param string $key
	 */
	public function release($key)
	{
		$this->_memCached->delete($key);
	}

	/**
	 *
	 * @param string $key
	 * @param string $str
	 * @return bool
	 */
	public function append($key, $str)
	{
		$log = LogHandler::getInstance();

		if (!HttpContext::getInstance()->getNoCache())
		{
			$log->info("[Cache] Append '$key' in Memcached");
			return $this->_memCached->append($key, $str);
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
		return;
	}

	/**
	 * UnLock resource after set it
	 * @param string $key
	 */
	public function unlock($key)
	{
		return;
	}

}
