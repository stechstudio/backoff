<?php
namespace STS\Backoff\Strategies;

use PHPUnit\Framework\TestCase;

class LinearStrategyTest extends TestCase
{
    public function testDefaults()
    {
        $s = new LinearStrategy();

        $this->assertEquals(100, $s->getBase());
    }

    public function testWaitTimes()
    {
        $s = new LinearStrategy(100);

        $this->assertEquals(100, $s->getWaitTime(1));
        $this->assertEquals(200, $s->getWaitTime(2));
        $this->assertEquals(300, $s->getWaitTime(3));
    }
}
