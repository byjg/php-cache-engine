<?php

namespace ByJG\Cache\Psr;

use ByJG\Cache\CacheEngineInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CachePool implements CacheItemPoolInterface
{
    /**
     * @var CacheEngineInterface
     */
    protected $_cacheEngine;

    /**
     * @var CacheItem
     */
    protected $_lastCacheItem;

    /**
     * @var int
     */
    protected $bufferSize = 10;

    /**
     * @var CacheItem[]
     */
    protected $buffer = [];

    /**
     * @var array
     */
    protected $bufferKeys = [];

    /**
     * CachePool constructor.
     * 
     * @param int $bufferSize
     * @param CacheEngineInterface $_cacheEngine
     */
    public function __construct(CacheEngineInterface $_cacheEngine, $bufferSize = 10)
    {
        $this->_cacheEngine = $_cacheEngine;
        $this->bufferSize = $bufferSize;
    }

    /**
     * @return int
     */
    public function getBufferSize()
    {
        return $this->bufferSize;
    }

    /**
     * @param int $bufferSize
     */
    public function setBufferSize($bufferSize)
    {
        $this->bufferSize = $bufferSize;
    }


    protected function addElementToBuffer(CacheItem $cacheItem)
    {
        if ($this->bufferSize < 1) {
            return;
        }

        $key = $cacheItem->getKey();
        $this->buffer[$key] = $cacheItem;

        if (in_array($key, $this->bufferKeys)) {
            return;
        }

        array_push($this->bufferKeys, $key);

        if (count($this->bufferKeys) > $this->bufferSize) {
            $element = array_shift($this->bufferKeys);
            unset($this->buffer[$element]);
        }
    }

    protected function removeElementFromBuffer($key)
    {
        $result = array_search($key, $this->bufferKeys);
        if ($result === false) {
            return;
        }

        unset($this->buffer[$key]);
        unset($this->bufferKeys[$result]);
    }

    public function getItem($key)
    {
        // Get the element from the buffer if still remains valid!
        if (in_array($key, $this->bufferKeys)) {
            $cacheItem = $this->buffer[$key];
            if ($cacheItem->getExpiresInSecs() > 1) {
                return $cacheItem;
            }
        }
        
        // Get the element from the cache!
        $result = $this->_cacheEngine->get($key);
        $cache = new CacheItem($key, $result, $result !== null);

        $this->addElementToBuffer($cache);

        return $cache;
    }

    public function getItems(array $keys = array())
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->getItem($key);
        }

        return $result;
    }

    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /** 
     * @todo Implement Clear Pool! 
     */
    public function clear()
    {
        $this->bufferKeys = [];
        $this->buffer = [];
    }

    public function deleteItem($key)
    {
        $this->deleteItem([$key]);
    }

    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            $this->_cacheEngine->release($key);
        }
    }

    public function save(CacheItemInterface $item)
    {
        if (!($item instanceof CacheItem)) {
            throw new \InvalidArgumentException('The cache item must be an implementation of \ByJG\Cache\Psr\CacheItem');
        }
        
        if ($item->getExpiresInSecs() < 1) {
            throw new \Psr\Log\InvalidArgumentException('Object has expired!');
        }
        
        $this->_cacheEngine->set($item->getKey(), $item->get(), $item->getExpiresInSecs());
        $this->addElementToBuffer($item);
    }

    /**
     * @var CacheItem[]
     */
    protected $deferredItem = [];
    
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItem[] = $item;
    }

    public function commit()
    {
        foreach ($this->deferredItem as $item) {
            $this->save($item);
        }
        
        $this->deferredItem = [];
    }
}
