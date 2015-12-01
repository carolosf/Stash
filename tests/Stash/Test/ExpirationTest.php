<?php

namespace Stash\Test;

use Memcached;
use Stash\Driver\Ephemeral;
use Stash\Driver\Memcache;
use Stash\Pool;

class ExpirationTest extends \PHPUnit_Framework_TestCase
{
    public function testMemcacheExpiration()
    {
        $memcacheDriver = new Memcache();
        $memcacheDriver->setOptions(
            [
                'servers' => ['localhost', 11211]
            ]
        );
        $pool = new Pool($memcacheDriver);


        $this->assertExpire($pool, '1,', 100);
        $this->assertExpire($pool, '1,', 10);
    }

    public function testMemcachedDriver()
    {
        $memcached = new Memcached();
        $memcached->addServer('localhost', 11211);
        $memcached->delete('int');

        $i = 0;
        $output = '';
        while ($i < 10) {
            ++$i;
            sleep(1);

            $result = $memcached->get('int');

            if (!$result) {
                $output .= $i . ',';
                $memcached->set('int', $i, time() + 10);
            }
        }

        $this->assertEquals('1,', $output);

    }

    public function testEphemeralExpiration()
    {
        $pool = new Pool(new Ephemeral());
        $this->assertExpire($pool, '1,', 100);
    }

    /**
     * Should only count every time the cache expires
     * @param $pool
     * @param $expected
     * @param $ttl
     */
    private function assertExpire($pool, $expected, $ttl)
    {
        $item = $pool->getItem('path/to/item');
        $item->clear();

        $i = 0;
        $output = '';
        while ($i < 10) {
            ++$i;
            sleep(1);

            $item->get();

            if ($item->isMiss()) {
                $output .= $i . ',';
                $item->set($i, $ttl);
            }
        }

        $this->assertEquals($expected, $output);
    }
}
