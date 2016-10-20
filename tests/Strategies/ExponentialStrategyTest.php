<?php
namespace STS\Backoff\Strategies;

use PHPUnit\Framework\TestCase;

class ExponentialStrategyTest extends TestCase
{
    public function testDefaults()
    {
        $s = new ExponentialStrategy();

        $this->assertEquals(100, $s->getBase());
    }

    public function testWaitTimes()
    {
        $s = new ExponentialStrategy(200);

        $this->assertEquals(200, $s->getWaitTime(1));
        $this->assertEquals(40000, $s->getWaitTime(2));
        $this->assertEquals(8000000, $s->getWaitTime(3));
    }
}
