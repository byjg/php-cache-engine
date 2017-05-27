<?php

namespace Test;

use ByJG\Cache\Engine\NoCacheEngine;

require_once 'BaseCacheTest.php';

class CachePSR16Test extends BaseCacheTest
{
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

    /**
     * @dataProvider CachePoolProvider
     * @param \ByJG\Cache\Engine\BaseCacheEngine $cacheEngine
     */
    public function testCacheObjectPsr16(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-16 Test
        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertEquals(null, $item);

            // Set object
            $model = new Model(10, 20);
            $cacheEngine->set('chave', $model);

            // Get Object
            if (!($cacheEngine instanceof \ByJG\Cache\Engine\NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals($model, $item2);
            }

            // Delete
            $cacheEngine->delete('chave');
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
    public function testClearPsr16(\ByJG\Cache\Engine\BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        // PSR-16 Test
        if ($cacheEngine->isAvailable()) {
            // Values
            $empty = [
                'chave'  => null,
                'chave2' => null,
                'chave3' => null
            ];
            $set = [
                'chave'  => 'val',
                'chave2' => 'val2',
                'chave3' => 'val3'
            ];

            // First time
            $item = $cacheEngine->getMultiple(['chave', 'chave2', 'chave3']);
            $this->assertEquals($empty, $item);

            // Set and Check
            $cacheEngine->setMultiple($set);
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item = $cacheEngine->getMultiple(['chave', 'chave2', 'chave3']);
                $this->assertEquals($set, $item);
            }

            // Clear and Check
            $cacheEngine->clear();
            $item = $cacheEngine->getMultiple(['chave', 'chave2', 'chave3']);
            $this->assertEquals($empty, $item);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }
}
