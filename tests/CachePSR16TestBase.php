<?php

namespace Tests;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\GarbageCollectorInterface;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
use PHPUnit\Framework\Attributes\DataProvider;

class CachePSR16TestBase extends TestBase
{
    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGetOneItem(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave', null);
            $this->assertNull($item);
            $item = $cacheEngine->get('chave', 'default');
            $this->assertEquals('default', $item);

            // Set object
            $cacheEngine->set('chave', 'valor');

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave', 'default');
                $this->assertEquals('valor', $item2);
            }

            // Remove
            $cacheEngine->delete('chave');

            // Check Removed
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGetMultipleItems(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $items = [...$cacheEngine->getMultiple(['chave1', 'chave2'])];
            $this->assertNull($items['chave1']);
            $this->assertNull($items['chave2']);
            $items = [...$cacheEngine->getMultiple(['chave1', 'chave2'], 'default')];
            $this->assertEquals('default', $items['chave1']);
            $this->assertEquals('default', $items['chave2']);

            // Set object
            $cacheEngine->set('chave1', 'valor1');
            $cacheEngine->set('chave2', 'valor2');

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = [...$cacheEngine->getMultiple(['chave1', 'chave2'])];
                $this->assertEquals('valor1', $item2['chave1']);
                $this->assertEquals('valor2', $item2['chave2']);
            }

            // Remove
            $cacheEngine->deleteMultiple(['chave1', 'chave2']);

            // Check Removed
            $items = [...$cacheEngine->getMultiple(['chave1', 'chave2'])];
            $this->assertNull($items['chave1']);
            $this->assertNull($items['chave2']);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testTtl(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
            $this->assertFalse($cacheEngine->has('chave'));
            $item2 = $cacheEngine->get('chave2');
            $this->assertNull($item2);
            $this->assertFalse($cacheEngine->has('chave2'));

            // Set object
            $cacheEngine->set('chave', 'valor', 2);
            $cacheEngine->set('chave2', 'valor2', 2);

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals('valor', $item2);
                $this->assertTrue($cacheEngine->has('chave2'));
                sleep(3);
                $item2 = $cacheEngine->get('chave');
                $this->assertNull($item2);
                $this->assertFalse($cacheEngine->has('chave2'));
            }
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheObject(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);

            // Set object
            $model = new Model(10, 20);
            $cacheEngine->set('chave', $model);

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals($model, $item2);
            }

            // Delete
            $cacheEngine->delete('chave');
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheArray(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);

            // Get Object
            if (($cacheEngine instanceof NoCacheEngine)) {
                return;
            }

            // Set object
            $cacheEngine->set('chave', [ 'a' => 10, 'b' => 20 ]);
            $this->assertEquals([ 'a' => 10, 'b' => 20 ], $cacheEngine->get('chave'));

            $cacheEngine->set('chave', [ 10, 20 ]);
            $this->assertEquals([ 10, 20 ], $cacheEngine->get('chave'));

            $cacheEngine->set('chave', [ 'a' => 10, 'b' => new Model(1, 2) ]);
            $this->assertEquals([ 'a' => 10, 'b' => new Model(1, 2) ], $cacheEngine->get('chave'));

            // Delete
            $cacheEngine->delete('chave');
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testClear(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

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

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheContainerKeyNonExistent(BaseCacheEngine $cacheEngine)
    {
        if ($cacheEngine->isAvailable()) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("Key 'abc' not found in container");

            $cacheEngine->withKeysFromContainer(new BasicContainer());
            $cacheEngine->set("abc", "30");
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheContainerKey(BaseCacheEngine $cacheEngine)
    {
        if ($cacheEngine->isAvailable() && !($cacheEngine instanceof NoCacheEngine)) {
            $cacheEngine->clear();

            // This first part will get the "test-key" from the container.
            // The real key is container["test-key"] = "container-key"
            $cacheEngine->withKeysFromContainer(new BasicContainer());
            $this->assertFalse($cacheEngine->has("test-key"));
            $cacheEngine->set("test-key", "something");
            $this->assertTrue($cacheEngine->has("test-key"));
            $this->assertEquals("something", $cacheEngine->get("test-key"));

            // This part, we will disable the container and try to get the original key from test.
            $cacheEngine->withKeysFromContainer(null);
            $this->assertTrue($cacheEngine->has("container-key"));
            $this->assertEquals("something", $cacheEngine->get("container-key"));
        } else {
            $this->markTestIncomplete('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGarbageCollector(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof GarbageCollectorInterface)) {
            // First time
            $cacheEngine->set('chave', "ok");
            $this->assertTrue($cacheEngine->has('chave'));
            $this->assertNull($cacheEngine->getTtl('chave'));
            $cacheEngine->delete('chave');
            $this->assertFalse($cacheEngine->has('chave'));

            // Set TTL
            $cacheEngine->set('chave', "ok", 1);
            $this->assertTrue($cacheEngine->has('chave'));
            $this->assertNotNull($cacheEngine->getTtl('chave'));
            $cacheEngine->collectGarbage();
            $this->assertTrue($cacheEngine->has('chave'));
            $this->assertNotNull($cacheEngine->getTtl('chave')); // Should not delete yet
            sleep(1);
            $cacheEngine->collectGarbage();
            $this->assertNull($cacheEngine->getTtl('chave')); // Should be deleted
            $this->assertFalse($cacheEngine->has('chave'));
        } else {
            $this->markTestIncomplete('Does not support garbage collector or it is native');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicIncrement(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $cacheEngine->set('chave', 10);
            $this->assertEquals(11, $cacheEngine->increment('chave'));
            $this->assertEquals(12, $cacheEngine->increment('chave'));
            $this->assertEquals(13, $cacheEngine->increment('chave'));
            $this->assertEquals(14, $cacheEngine->increment('chave'));
            $this->assertEquals(15, $cacheEngine->increment('chave'));
            $this->assertEquals(15, $cacheEngine->get('chave'));
        } else {
            $this->markTestIncomplete('Does not support atomic increment or it is native');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicDecrement(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $cacheEngine->set('chave', 10);
            $this->assertEquals(9, $cacheEngine->decrement('chave'));
            $this->assertEquals(8, $cacheEngine->decrement('chave'));
            $this->assertEquals(7, $cacheEngine->decrement('chave'));
            $this->assertEquals(6, $cacheEngine->decrement('chave'));
            $this->assertEquals(5, $cacheEngine->decrement('chave'));
            $this->assertEquals(5, $cacheEngine->get('chave'));
        } else {
            $this->markTestIncomplete('Does not support atomic decrement or it is native');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicAdd(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $this->assertEquals([10], $cacheEngine->add('chave', 10));
            $this->assertEquals([10, 20], $cacheEngine->add('chave', 20));
            $this->assertEquals([10, 20, "value"], $cacheEngine->add('chave', "value"));
            $this->assertEquals([10, 20, "value"], $cacheEngine->get('chave'));

            $cacheEngine->set('chave', 10);
            $this->assertEquals(10, $cacheEngine->get('chave'));
            $this->assertEquals([10, 20], $cacheEngine->add('chave', 20));

            $cacheEngine->set('chave', ["A", "B"]);
            $this->assertEquals(["A", "B"], $cacheEngine->get('chave'));
            $this->assertEquals(["A", "B", "C"], $cacheEngine->add('chave', "C"));

            $cacheEngine->set('chave', new Model(10, 20));
            $this->assertEquals(new Model(10, 20), $cacheEngine->get('chave'));
            $this->assertEquals([new Model(10, 20), new Model(20, 30)], $cacheEngine->add('chave', new Model(20, 30)));

            $cacheEngine->set('chave', [new Model(10, 20)]);
            $this->assertEquals([new Model(10, 20)], $cacheEngine->get('chave'));
            $this->assertEquals([new Model(10, 20), new Model(20, 30)], $cacheEngine->add('chave', new Model(20, 30)));


        } else {
            $this->markTestIncomplete('Does not support atomic add or it is native');
        }
    }
}
