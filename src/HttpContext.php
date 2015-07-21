<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\Cache;

/**
 * Description of HttpContext
 *
 * @author jg
 */
class HttpContext
{
    use \ByJG\DesignPattern\Singleton;

    private $reset;
    private $noCache;

    /**
     *
     * @var \Iconfig\Config
     */
    private $config;

    protected function __construct()
    {
        $this->reset = isset($_REQUEST['reset']) ? strtolower($_REQUEST['reset']) === 'true' : false;
        $this->noCache = (isset($_REQUEST['nocache']) ? strtolower($_REQUEST['nocache']) === 'true' : false) || $this->reset;
        $this->config = new \Iconfig\Config('config');
    }

    public function getReset()
    {
        return $this->reset;
    }

    public function getNoCache()
    {
        return $this->noCache;
    }

    public function setReset($reset)
    {
        $this->reset = $reset;
    }

    public function setNoCache($noCache)
    {
        $this->noCache = $noCache;
    }

    /**
     *
     * @return ICacheEngine
     */
    public static function factory()
    {
        return self::getInstance()->factoryInternal();
    }

    protected function factoryInternal()
    {
        print_r($this->config->getAll());
        $result = $this->config->getCacheconfig('default');
        if (is_null($result)) {
            throw new \Exception("The cache config 'default' was not found");
        }
        $resultPrep = str_replace('.', '\\', $result);

        return $resultPrep::getInstance();

    }

    public function getMemcachedConfig()
    {
        return $this->config->getCacheconfig('memcached');
    }
}
