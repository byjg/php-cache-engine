<?php

namespace ByJG\Cache;
use ByJG\Cache\Psr\CachePool;
use ByJG\DesignPattern\Singleton;
use Iconfig\Config;

/**
 * Description of CacheContext
 *
 * @author jg
 */
class CacheContext
{

    use Singleton;

    private $reset;
    private $noCache;

    /**
     *
     * @var Config
     */
    private $config;

    protected function __construct()
    {
        $this->reset = isset($_REQUEST['reset']) ? strtolower($_REQUEST['reset']) === 'true' : false;
        $this->noCache = (isset($_REQUEST['nocache']) ? strtolower($_REQUEST['nocache']) === 'true' : false) || $this->reset;

        try {
            $this->config = new Config('config');
        } catch (\RuntimeException $ex) {
            throw new \RuntimeException("[Cache Engine]: There is no folder 'config' in root path");
        }
    }

    private static $instances = [];

    /**
     *
     * @param string $key
     * @return CacheEngineInterface
     */
    public static function factory($key = "default")
    {
        return self::getInstance()->factoryInternal($key);
    }
    
    /**
     *
     * @param string $key
     * @return CachePool
     */
    public static function psrFactory($key = "default")
    {
        return new CachePool(self::getInstance()->factoryInternal($key), self::getInstance()->getPoolBuffer($key));
    }
    
    private function factoryInternal($key)
    {
        if (!isset(self::$instances[$key])) {
            $result = $this->config->getCacheconfig("$key.instance");
            if (is_null($result)) {
                throw new \Exception("The cache config '$key' was not found");
            }
            $resultPrep = str_replace('.', '\\', $result);

            $instance = new $resultPrep();
            $instance->configKey = $key; // This is not in the interface;

            self::$instances[$key] = $instance;
        }

        return self::$instances[$key];
    }

    public function getMemcachedConfig($key = "default")
    {
        return $this->config->getCacheconfig("$key.memcached");
    }

    public function getShmopConfig($key = 'default')
    {
        return $this->config->getCacheconfig("$key.shmop");
    }

    public function getPoolBuffer($key = 'default')
    {
        $bufferSize = $this->config->getCacheconfig("$key.poolbuffer");
        
        if ($bufferSize === null) {
            return 10;
        }
        return (int)$bufferSize;
    }
}
