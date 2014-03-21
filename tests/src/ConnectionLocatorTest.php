<?php
namespace Aura\Sql;

use Aura\Sql\Query\QueryFactory;

class ConnectionLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionLocator
     */
    protected $locator;
    
    protected $tmp;
    
    protected $default;
    
    protected $read = array();
    
    protected $write = array();
    
    protected function setUp()
    {
        $this->tmp = $tmp = sys_get_temp_dir();

        $this->default = function () use ($tmp) {
            return new ExtendedPdo(
                sprintf('sqlite:%sdefault_example.sqlite', $tmp),
                'user_name',
                'pass_word',
                array()
            );
        };
        
        $this->read = array(
            'read1' => function () use ($tmp) {
                return new ExtendedPdo(
                    sprintf('sqlite:%sread1_example.sqlite', $tmp),
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'read2' => function () use ($tmp) {
                return new ExtendedPdo(
                    sprintf('sqlite:%sread2_example.sqlite', $tmp),
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'read3' => function () use ($tmp) {
                return new ExtendedPdo(
                    sprintf('sqlite:%sread3_example.sqlite', $tmp),
                    'user_name',
                    'pass_word',
                    array()
                );
            },
        );
        
        $this->write = array(
            'write1' => function () use ($tmp) {
                return new ExtendedPdo(
                    sprintf('sqlite:%swrite1_example.sqlite', $tmp),
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'write2' => function () use ($tmp) {
                return new ExtendedPdo(
                    sprintf('sqlite:%swrite2_example.sqlite', $tmp),
                    'user_name',
                    'pass_word',
                    array()
                );
            },
            'write3' => function () use ($tmp) {
                return new ExtendedPdo(
                    sprintf('sqlite:%swrite3_example.sqlite', $tmp),
                    'user_name',
                    'pass_word',
                    array()
                );
            },
        );
    }

    /**
     * Cleanup any leftover db files after the test has been run
     */
    protected function tearDown()
    {
        $dbs = array(
            sprintf('%sdefault_example.sqlite', $this->tmp),
            sprintf('%sread1_example.sqlite', $this->tmp),
            sprintf('%sread2_example.sqlite', $this->tmp),
            sprintf('%sread3_example.sqlite', $this->tmp),
            sprintf('%swrite1_example.sqlite', $this->tmp),
            sprintf('%swrite2_example.sqlite', $this->tmp),
            sprintf('%swrite3_example.sqlite', $this->tmp),
        );
        
        foreach ($dbs as $db) {
            if (file_exists($db)) {
                unlink($db);
            }
        }
    }
    
    protected function newLocator($read = array(), $write = array())
    {
        return new ConnectionLocator($this->default, $read, $write);
    }
    
    public function testGetDefault()
    {
        $locator = $this->newLocator();
        $pdo = $locator->getDefault();
        $expect = sprintf('sqlite:%sdefault_example.sqlite', $this->tmp);
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }
    
    public function testGetReadDefault()
    {
        $locator = $this->newLocator();
        $pdo = $locator->getRead();
        $expect = sprintf('sqlite:%sdefault_example.sqlite', $this->tmp);
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }
    
    public function testGetReadRandom()
    {
        $locator = $this->newLocator($this->read, $this->write);
        
        $expect = array(
            sprintf('sqlite:%sread1_example.sqlite', $this->tmp),
            sprintf('sqlite:%sread2_example.sqlite', $this->tmp),
            sprintf('sqlite:%sread3_example.sqlite', $this->tmp),
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
        $expect = sprintf('sqlite:%sread2_example.sqlite', $this->tmp);
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
        $expect = sprintf('sqlite:%sdefault_example.sqlite', $this->tmp);
        $actual = $pdo->getDsn();
        $this->assertSame($expect, $actual);
    }
    
    public function testGetWriteRandom()
    {
        $locator = $this->newLocator($this->write, $this->write);
        
        $expect = array(
            sprintf('sqlite:%swrite1_example.sqlite', $this->tmp),
            sprintf('sqlite:%swrite2_example.sqlite', $this->tmp),
            sprintf('sqlite:%swrite3_example.sqlite', $this->tmp),
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
        $expect = sprintf('sqlite:%swrite2_example.sqlite', $this->tmp);
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
