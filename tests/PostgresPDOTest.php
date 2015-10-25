<?php
namespace Aura\Sql;

use PDO;
use Rebuilder;

class PostgresPDOTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $pdo->setRebuilder(new \Aura\Sql\PostgresRebuilder());
        return $pdo;
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
