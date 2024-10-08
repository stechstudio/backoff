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
        $this->assertEquals(400, $s->getWaitTime(2));
        $this->assertEquals(800, $s->getWaitTime(3));
        $this->assertEquals(1600, $s->getWaitTime(4));
        $this->assertEquals(3200, $s->getWaitTime(5));
        $this->assertEquals(6400, $s->getWaitTime(6));
        $this->assertEquals(12800, $s->getWaitTime(7));
        $this->assertEquals(25600, $s->getWaitTime(8));
    }

    public function testWaitTimesWithDefault()
    {
        $strategy = new ExponentialStrategy();
        $base = $strategy->getBase();
        $this->assertEquals((int) ($base * pow(2, 0)), $strategy->getWaitTime(1));
        $this->assertEquals((int) ($base * pow(2, 1)), $strategy->getWaitTime(2));
        $this->assertEquals((int) ($base * pow(2, 2)), $strategy->getWaitTime(3));
    }
}
