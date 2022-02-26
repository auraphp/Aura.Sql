<?php
namespace Aura\Sql\Profiler;

use Psr\Log\LogLevel;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ProfilerTest extends TestCase
{
    protected function set_up()
    {
        $this->profiler = new Profiler();
    }

    public function testActive()
    {
        $this->assertFalse($this->profiler->isActive());
        $this->profiler->setActive(true);
        $this->assertTrue($this->profiler->isActive());
    }

    public function testLogLevel()
    {
        $this->assertSame(LogLevel::DEBUG, $this->profiler->getLogLevel());
        $this->profiler->setLogLevel(LogLevel::INFO);
        $this->assertSame(LogLevel::INFO, $this->profiler->getLogLevel());
    }

    public function testLogFormat()
    {
        $format = '{function} ({duration} seconds): {statement} {backtrace}';
        $this->assertSame($format, $this->profiler->getLogFormat());

        $format = 'foo';
        $this->profiler->setLogFormat($format);
        $this->assertSame($format, $this->profiler->getLogFormat());
    }
}
