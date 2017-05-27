<?php

use ByJG\Cache\Psr6\CachePool;


// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class CacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \ByJG\Cache\Engine\BaseCacheEngine
     */
    private $cacheEngine = null;

    protected function setUp()
    {

    }

    protected function tearDown()
    {
        $this->cacheEngine->clear();
        $this->cacheEngine = null;
    }

    public function CachePoolProvider()
    {
        $memcachedServer = ['memcached-container:11211'];
        $redisCacheServer = 'redis-container:6379';
        $redisPassword = '';

        return [
            'Array' => [
                new \ByJG\Cache\Engine\ArrayCacheEngine()
            ],
            'FileSystem' => [
                new \ByJG\Cache\Engine\FileSystemCacheEngine()
            ],
            'ShmopCache' => [
                new \ByJG\Cache\Engine\ShmopCacheEngine()
            ],
            'SessionCache' => [
                new \ByJG\Cache\Engine\SessionCacheEngine()
            ],
            'NoCacheEngine' => [
                new \ByJG\Cache\Engine\NoCacheEngine()
            ],
            // [
            //     new \ByJG\Cache\Engine\MemcachedEngine($memcachedServer)
            // ],
            // [
            //     new \ByJG\Cache\Engine\RedisCacheEngine($redisCacheServer, $redisPassword)
            // ]
        ];
    }

    /**
     * @dataProvider CachePoolProvider
     * @param \ByJG\Cache\Engine\BaseCacheEngine $cacheEngine
     */
    public function testGetOneItemPsr6(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-6 Test
        $object = new CachePool($cacheEngine);
        if ($object->isAvailable()) {
            // First time
            $item = $object->getItem('chave');
            $this->assertFalse($item->isHit());

            // Set object
            $item->set('valor');
            $object->save($item);
            $this->assertTrue($item->isHit());

            // Get Object
            $item2 = $object->getItem('chave');
            $this->assertTrue($item2->isHit());
            $this->assertEquals('valor', $item2->get());

            // Remove
            $object->deleteItem('chave');

            // Check Removed
            $item = $object->getItem('chave');
            $this->assertFalse($item->isHit());
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @dataProvider CachePoolProvider
     * @param \ByJG\Cache\Engine\BaseCacheEngine $cacheEngine
     */
    public function testGetMultipleItemsPsr6(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-6 Test
        $object = new CachePool($cacheEngine);
        if ($object->isAvailable()) {
            // First time
            $items = $object->getItems(['chave1', 'chave2']);
            $this->assertFalse($items[0]->isHit());
            $this->assertFalse($items[1]->isHit());

            // Set object
            $items[0]->set('valor1');
            $items[1]->set('valor2');
            $object->saveDeferred($items[0]);
            $object->saveDeferred($items[1]);
            $object->commit();
            $this->assertTrue($items[0]->isHit());
            $this->assertTrue($items[1]->isHit());

            // Get Object
            $item2 = $object->getItems(['chave1', 'chave2']);
            $this->assertTrue($item2[0]->isHit());
            $this->assertTrue($item2[1]->isHit());
            $this->assertEquals('valor1', $item2[0]->get());
            $this->assertEquals('valor2', $item2[1]->get());

            // Remove
            $object->deleteItems(['chave1', 'chave2']);

            // Check Removed
            $items = $object->getItems(['chave1', 'chave2']);
            $this->assertFalse($items[0]->isHit());
            $this->assertFalse($items[1]->isHit());
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @dataProvider CachePoolProvider
     * @param \ByJG\Cache\Engine\BaseCacheEngine $cacheEngine
     */
    public function testGetOneItemPsr16(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-6 Test
        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave', null);
            $this->assertEquals(null, $item);
            $item = $cacheEngine->get('chave', 'default');
            $this->assertEquals('default', $item);

            // Set object
            $cacheEngine->set('chave', 'valor');

            // Get Object
            if (!($cacheEngine instanceof \ByJG\Cache\Engine\NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave', 'default');
                $this->assertEquals('valor', $item2);
            }

            // Remove
            $cacheEngine->delete('chave');

            // Check Removed
            $item = $cacheEngine->get('chave');
            $this->assertEquals(null, $item);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @dataProvider CachePoolProvider
     * @param \ByJG\Cache\Engine\BaseCacheEngine $cacheEngine
     */
    public function testGetMultipleItemsPsr16(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-6 Test
        if ($cacheEngine->isAvailable()) {
            // First time
            $items = $cacheEngine->getMultiple(['chave1', 'chave2']);
            $this->assertEquals(null, $items['chave1']);
            $this->assertEquals(null, $items['chave2']);
            $items = $cacheEngine->getMultiple(['chave1', 'chave2'], 'default');
            $this->assertEquals('default', $items['chave1']);
            $this->assertEquals('default', $items['chave2']);

            // Set object
            $cacheEngine->set('chave1', 'valor1');
            $cacheEngine->set('chave2', 'valor2');

            // Get Object
            if (!($cacheEngine instanceof \ByJG\Cache\Engine\NoCacheEngine)) {
                $item2 = $cacheEngine->getMultiple(['chave1', 'chave2']);
                $this->assertEquals('valor1', $item2['chave1']);
                $this->assertEquals('valor2', $item2['chave2']);
            }

            // Remove
            $cacheEngine->deleteMultiple(['chave1', 'chave2']);

            // Check Removed
            $items = $cacheEngine->getMultiple(['chave1', 'chave2']);
            $this->assertEquals(null, $items['chave1']);
            $this->assertEquals(null, $items['chave2']);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @dataProvider CachePoolProvider
     * @param \ByJG\Cache\Engine\BaseCacheEngine $cacheEngine
     */
    public function testTtlPsr16(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-6 Test
        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertEquals(null, $item);
            $this->assertFalse($cacheEngine->has('chave'));
            $item2 = $cacheEngine->get('chave2');
            $this->assertEquals(null, $item2);
            $this->assertFalse($cacheEngine->has('chave2'));

            // Set object
            $cacheEngine->set('chave', 'valor', 2);
            $cacheEngine->set('chave2', 'valor2', 2);

            // Get Object
            if (!($cacheEngine instanceof \ByJG\Cache\Engine\NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals('valor', $item2);
                $this->assertTrue($cacheEngine->has('chave2'));
                sleep(3);
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals(null, $item2);
                $this->assertFalse($cacheEngine->has('chave2'));
            }
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }
}
