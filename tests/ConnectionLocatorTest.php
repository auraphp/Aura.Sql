<?php
namespace Aura\Sql;

use Aura\Sql\Query\QueryFactory;

class ConnectionLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionLocator
     */
    protected $locator;

    protected $default;

    protected $read = array();

    protected $write = array();

    protected function setUp()
    {
        $this->default = function () {
            return new ExtendedPdo(
                'mock:host=default.example.com',
                'user_name',
                'pass_word',
                array()
            );
        };

        $this->read = array(
            'read1' => function () {
                return new ExtendedPdo(
                    'mock:host=read1.example.com',
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'read2' => function () {
                return new ExtendedPdo(
                    'mock:host=read2.example.com',
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'read3' => function () {
                return new ExtendedPdo(
                    'mock:host=read3.example.com',
                    'user_name',
                    'pass_word',
                    array()
                );
            },
        );

        $this->write = array(
            'write1' => function () {
                return new ExtendedPdo(
                    'mock:host=write1.example.com',
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'write2' => function () {
                return new ExtendedPdo(
                    'mock:host=write2.example.com',
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'write3' => function () {
                return new ExtendedPdo(
                    'mock:host=write3.example.com',
                    'user_name',
                    'pass_word',
                    array()
                );
            },
        );
    }

    protected function newLocator($read = array(), $write = array())
    {
        return new ConnectionLocator($this->default, $read, $write);
    }

    public function testGetDefault()
    {
        $locator = $this->newLocator();
        $pdo = $locator->getDefault();
        $expect = 'mock:host=default.example.com';
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }

    public function testGetReadDefault()
    {
        $locator = $this->newLocator();
        $pdo = $locator->getRead();
        $expect = 'mock:host=default.example.com';
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }

    public function testGetReadRandom()
    {
        $locator = $this->newLocator($this->read, $this->write);

        $expect = array(
            'mock:host=read1.example.com',
            'mock:host=read2.example.com',
            'mock:host=read3.example.com',
        );

        // try 10 times to make sure we get lots of random responses
        for ($i = 1; $i <= 10; $i++) {
            $pdo = $locator->getRead();
            $actual = $pdo->getDsn();
            $this->assertTrue(in_array($actual, $expect));
        }
    }

    public function testGetReadName()
    {
        $locator = $this->newLocator($this->read, $this->write);
        $pdo = $locator->getRead('read2');
        $expect = 'mock:host=read2.example.com';
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }

    public function testGetReadMissing()
    {
        $locator = $this->newLocator($this->read, $this->write);
        $this->setExpectedException('Aura\Sql\Exception\ConnectionNotFound');
        $pdo = $locator->getRead('no-such-connection');
    }

    public function testGetWriteDefault()
    {
        $locator = $this->newLocator();
        $pdo = $locator->getWrite();
        $expect = 'mock:host=default.example.com';
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }

    public function testGetWriteRandom()
    {
        $locator = $this->newLocator($this->write, $this->write);

        $expect = array(
            'mock:host=write1.example.com',
            'mock:host=write2.example.com',
            'mock:host=write3.example.com',
        );

        // try 10 times to make sure we get lots of random responses
        for ($i = 1; $i <= 10; $i++) {
            $pdo = $locator->getWrite();
            $actual = $pdo->getDsn();
            $this->assertTrue(in_array($actual, $expect));
        }
    }

    public function testGetWriteName()
    {
        $locator = $this->newLocator($this->write, $this->write);
        $pdo = $locator->getWrite('write2');
        $expect = 'mock:host=write2.example.com';
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }

    public function testGetWriteMissing()
    {
        $locator = $this->newLocator($this->write, $this->write);
        $this->setExpectedException('Aura\Sql\Exception\ConnectionNotFound');
        $pdo = $locator->getWrite('no-such-connection');
    }

    public function testIsInstanceOfConnectionLocator()
    {
        $this->assertInstanceOf('\Aura\Sql\ConnectionLocator', new ConnectionLocator());
    }
}
