<?php
namespace STS\Backoff;

use PHPUnit\Framework\TestCase;
use STS\Backoff\Strategies\ConstantStrategy;

class HelpersTest extends TestCase
{
    public function testSuccessWithDefaults()
    {
        $result = backoff(function() {
            return "success";
        });

        $this->assertEquals("success", $result);
    }

    public function testFailureWithDefaults()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("failure");

        backoff(function() {
            throw new \Exception("failure");
        }, 2);
    }

    public function testStrategy()
    {
        $start = microtime(true);

        // We're going to run a test for 100 attempts, just to verify we were able to
        // set our own strategy with a low sleep time.

        try {
            backoff(function() {
                throw new \Exception("failure");
            }, 100, new ConstantStrategy(1));
        } catch(\Exception $e) {}

        $end = microtime(true);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just a bit over the 100ms that we slept
        $this->assertTrue($elapsedMS > 100 && $elapsedMS < 200);
    }

    public function testWaitCap()
    {
        $start = microtime(true);

        // We're going to specify a really long sleep time, but with a short cap to override.

        try {
            backoff(function() {
                throw new \Exception("failure");
            }, 2, new ConstantStrategy(100000), 100);
        } catch(\Exception $e) {}

        $end = microtime(true);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just a bit over the 100ms that we slept
        $this->assertTrue($elapsedMS > 90 && $elapsedMS < 150,
            sprintf("Expected elapsedMS between 90 & 200, got: $elapsedMS\n"));
    }
}
