<?php

namespace Stash\Test;

use Memcached;
use Stash\Driver\Ephemeral;
use Stash\Driver\Memcache;
use Stash\Invalidation;
use Stash\Pool;

class ExpirationTest extends \PHPUnit_Framework_TestCase
{
    public function testMemcacheNormalExpiration()
    {
        $memcacheDriver = new Memcache();
        $memcacheDriver->setOptions(
            [
                'servers' => ['localhost', 11211]
            ]
        );
        $pool = new Pool($memcacheDriver);

        $this->assertExpire($pool, '1,', 50, Invalidation::NONE);
        $this->assertExpire($pool, '1,10,20,', 10, Invalidation::NONE);
    }

    public function testMemcacheBadExpiration()
    {
        $memcacheDriver = new Memcache();
        $memcacheDriver->setOptions(
            [
                'servers' => ['localhost', 11211]
            ]
        );
        $pool = new Pool($memcacheDriver);


        $this->assertExpire($pool, '1,10,20,', 10, Invalidation::PRECOMPUTE);
        // src/Stash/Item.php:503
    }

    public function testMemcachedDriver()
    {
        $memcached = new Memcached();
        $memcached->addServer('localhost', 11211);
        $memcached->delete('int');

        $i = 0;
        $output = '';
        while ($i < 20) {
            ++$i;
            sleep(1);

            $result = $memcached->get('int');

            if (!$result) {
                $output .= $i . ',';
                $memcached->set('int', $i, time() + 10);
            }
        }

        $this->assertEquals('1,11,', $output);

    }

    public function EphemeralExpiration()
    {
        $pool = new Pool(new Ephemeral());
        $this->assertExpire($pool, '1,', 100);
    }

    /**
     * Should only count every time the cache expires
     * @param Pool $pool
     * @param string $expected
     * @param integer $ttl
     * @param $invalidation
     */
    private function assertExpire(Pool $pool, $expected, $ttl, $invalidation = 0)
    {
        $item = $pool->getItem('path/to/item');
        $item->clear();

        $i = 0;
        $output = '';
        while ($i < 20) {
            ++$i;
            sleep(1);

            $item->get($invalidation);

            if ($item->isMiss()) {
                $output .= $i . ',';
                $item->set($i, $ttl);
            }
        }

        $this->assertEquals($expected, $output);
    }
}
