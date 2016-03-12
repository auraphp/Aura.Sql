<?php
namespace Aura\Sql;

use PDO;

class LazyExtendedPdoTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        return new LazyExtendedPdo('sqlite::memory:');
    }

    public function testGetPdo()
    {
        $lazy_pdo = $this->pdo->getPdo();
        $this->assertInstanceOf('PDO', $lazy_pdo);
        $this->assertNotSame($this->pdo, $lazy_pdo);
    }

    public function testConnect()
    {
        $pdo = new LazyExtendedPdo('sqlite::memory:');
        $this->assertFalse($pdo->isConnected());
        $pdo->connect();
        $this->assertTrue($pdo->isConnected());
        $pdo->disconnect();
        $this->assertFalse($pdo->isConnected());
    }

    public function testSetAndGetAttribute()
    {
        $pdo = new LazyExtendedPdo('sqlite::memory:');
        $this->assertFalse($pdo->isConnected());

        $pdo->setAttribute(PDO::ATTR_ERRMODE, ExtendedPdo::ERRMODE_WARNING);
        $this->assertFalse($pdo->isConnected());

        $actual = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->assertSame(PDO::ERRMODE_WARNING, $actual);
        $this->assertTrue($pdo->isConnected());

        // set again now that we're connected
        $pdo->setAttribute(PDO::ATTR_ERRMODE, ExtendedPdo::ERRMODE_EXCEPTION);
        $actual = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $actual);
    }
}
