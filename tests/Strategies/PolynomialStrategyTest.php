<?php
namespace STS\Backoff\Strategies;

use PHPUnit\Framework\TestCase;

class PolynomialStrategyTest extends TestCase
{
    public function testDefaults()
    {
        $s = new PolynomialStrategy();

        $this->assertEquals(100, $s->getBase());
        $this->assertEquals(2, $s->getDegree());
    }

    public function testWaitTimes()
    {
        $s = new PolynomialStrategy(100, 3);

        $this->assertEquals(100, $s->getWaitTime(1));
        $this->assertEquals(800, $s->getWaitTime(2));
        $this->assertEquals(2700, $s->getWaitTime(3));
    }
}
