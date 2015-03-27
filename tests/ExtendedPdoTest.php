<?php
namespace Aura\Sql;

use PDO;

class ExtendedPdoTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        return new ExtendedPdo('sqlite::memory:');
    }

    public function testGetPdo()
    {
        $lazy_pdo = $this->pdo->getPdo();
        $this->assertInstanceOf('PDO', $lazy_pdo);
        $this->assertNotSame($this->pdo, $lazy_pdo);
    }

    public function testDisconnect()
    {
        // connect
        $this->pdo->connect();
        $this->assertTrue($this->pdo->isConnected());

        // disconnect
        $this->pdo->disconnect();
        $this->assertFalse($this->pdo->isConnected());

        // reconnect
        $this->pdo->connect();
        $this->assertTrue($this->pdo->isConnected());
    }
}
