<?php
/**
 * User: jg
 * Date: 04/02/17
 * Time: 18:21
 */


use ByJG\Cache\Psr\CachePool;


class CachePoolTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {

    }

    protected function tearDown()
    {

    }

    public function CachePoolProvider()
    {
        // $memcachedServer = ['192.168.1.181:11211'];
        $memcachedServer = ['127.0.0.1:11211'];

        return [
            [
                new CachePool(new \ByJG\Cache\Engine\ArrayCacheEngine())
            ],
            [
                new CachePool(new \ByJG\Cache\Engine\FileSystemCacheEngine())
            ],
            [
                new CachePool(new \ByJG\Cache\Engine\ShmopCacheEngine())
            ],
            [
                new CachePool(new \ByJG\Cache\Engine\SessionCacheEngine())
            ],
            [
                new CachePool(new \ByJG\Cache\Engine\NoCacheEngine())
            ],
            [
                new CachePool(new \ByJG\Cache\Engine\MemcachedEngine($memcachedServer))
            ]
        ];
    }

    /**
     * @dataProvider CachePoolProvider
     * @param CachePool $object
     */
    public function testGetOneItem($object)
    {
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
     * @param CachePool $object
     */
    public function testGetMultipleItems($object)
    {
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

}
