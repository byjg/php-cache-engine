<?php

namespace Tests;

use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr6\CachePool;
use DateInterval;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\InvalidArgumentException;

class CachePSR6TestBase extends TestBase
{
    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGetOneItem(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

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
     * @param BaseCacheEngine $cacheEngine
     * @throws InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGetMultipleItems(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

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
     * @param BaseCacheEngine $cacheEngine
     * @throws InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testTtl(BaseCacheEngine $cacheEngine)
    {
        $timeList = [
            2,
            DateInterval::createFromDateString("2 seconds")
        ];

        foreach ($timeList as $time) {
            $this->cacheEngine = $cacheEngine;

            $object = new CachePool($cacheEngine);
            if ($object->isAvailable()) {
                // First time
                $item = $object->getItem('chave');
                $this->assertFalse($item->isHit());

                // Set object
                $item->set('valor');
                $item->expiresAfter($time);
                $object->save($item);
                $this->assertTrue($item->isHit());

                // Get Object
                $item2 = $object->getItem('chave');
                $this->assertTrue($item2->isHit());
                $this->assertEquals('valor', $item2->get());
                sleep(3);
                $item3 = $object->getItem('chave');
                $this->assertFalse($item3->isHit());
                $this->assertEquals(null, $item3->get());

                // Remove
                $object->deleteItem('chave');

                // Check Removed
                $item = $object->getItem('chave');
                $this->assertFalse($item->isHit());
            } else {
                $this->markTestIncomplete('Object is not fully functional');
            }
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheObject(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        $object = new CachePool($cacheEngine);
        if ($object->isAvailable()) {
            // First time
            $item = $object->getItem('chave');
            $this->assertFalse($item->isHit());

            // Set object
            $model = new Model(10, 20);
            $item->set($model);
            $object->save($item);
            $this->assertTrue($item->isHit());

            // Get Object
            $item2 = $object->getItem('chave');
            $this->assertTrue($item2->isHit());
            $this->assertEquals($model, $item2->get());

            // Remove
            $object->deleteItem('chave');

            // Check Removed
            $item = $object->getItem('chave');
            $this->assertFalse($item->isHit());
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }
}
