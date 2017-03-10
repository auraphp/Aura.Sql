<?php
namespace Aura\Sql;

class ConnectionLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionLocator
     */
    protected $locator;

    protected $conns;

    protected $default;

    protected $read = [];

    protected $write = [];

    protected function setUp()
    {
        $this->conns = [
            'default' => new ExtendedPdo('sqlite::memory:'),
            'read1' => new ExtendedPdo('sqlite::memory:'),
            'read2' => new ExtendedPdo('sqlite::memory:'),
            'read3' => new ExtendedPdo('sqlite::memory:'),
            'write1' => new ExtendedPdo('sqlite::memory:'),
            'write2' => new ExtendedPdo('sqlite::memory:'),
            'write3' => new ExtendedPdo('sqlite::memory:'),
        ];

        $conns = $this->conns;
        $this->default = function () use ($conns) { return $conns['default']; };
        $this->read = [
            'read1' => function () use ($conns) { return $conns['read1']; },
            'read2' => function () use ($conns) { return $conns['read2']; },
            'read3' => function () use ($conns) { return $conns['read3']; },
        ];
        $this->write = [
            'write1' => function () use ($conns) { return $conns['write1']; },
            'write2' => function () use ($conns) { return $conns['write2']; },
            'write3' => function () use ($conns) { return $conns['write3']; },
        ];
    }

    protected function newLocator($read = [], $write = [])
    {
        return new ConnectionLocator($this->default, $read, $write);
    }

    public function testGetDefault()
    {
        $locator = $this->newLocator();
        $actual = $locator->getDefault();
        $expect = $this->conns['default'];
        $this->assertSame($expect, $actual);
    }

    public function testGetReadDefault()
    {
        $locator = $this->newLocator();
        $actual = $locator->getRead();
        $expect = $this->conns['default'];
        $this->assertSame($expect, $actual);
    }

    public function testGetReadRandom()
    {
        $locator = $this->newLocator($this->read, $this->write);

        $expect = [
            $this->conns['read1'],
            $this->conns['read2'],
            $this->conns['read3'],
        ];

        // try 10 times to make sure we get lots of random responses
        for ($i = 1; $i <= 10; $i++) {
            $actual = $locator->getRead();
            $this->assertTrue(in_array($actual, $expect, true));
        }
    }

    public function testGetReadName()
    {
        $locator = $this->newLocator($this->read, $this->write);
        $actual = $locator->getRead('read2');
        $expect = $this->conns['read2'];
        $this->assertSame($expect, $actual);
    }

    public function testGetReadMissing()
    {
        $locator = $this->newLocator($this->read, $this->write);
        $this->expectException('Aura\Sql\Exception\ConnectionNotFound');
        $locator->getRead('no-such-connection');
    }

    public function testGetWriteDefault()
    {
        $locator = $this->newLocator();
        $actual = $locator->getWrite();
        $expect = $this->conns['default'];
        $this->assertSame($expect, $actual);
    }

    public function testGetWriteRandom()
    {
        $locator = $this->newLocator($this->read, $this->write);

        $expect = [
            $this->conns['write1'],
            $this->conns['write2'],
            $this->conns['write3'],
        ];

        // try 10 times to make sure we get lots of random responses
        for ($i = 1; $i <= 10; $i++) {
            $actual = $locator->getWrite();
            $this->assertTrue(in_array($actual, $expect, true));
        }
    }

    public function testGetWriteName()
    {
        $locator = $this->newLocator($this->read, $this->write);
        $actual = $locator->getWrite('write2');
        $expect = $this->conns['write2'];
        $this->assertSame($expect, $actual);
    }

    public function testGetWriteMissing()
    {
        $locator = $this->newLocator($this->read, $this->write);
        $this->expectException('Aura\Sql\Exception\ConnectionNotFound');
        $locator->getWrite('no-such-connection');
    }
}
