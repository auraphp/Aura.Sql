<?php
namespace Aura\Sql;

use PDO;

class DecoratedPdoTest extends ExtendedPdoTest
{
    private $innerPdo;

    protected function newPdo()
    {
        return new DecoratedPdo($this->innerPdo = new PDO('sqlite::memory:'));
    }

    public function testDisconnect()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->expectException(Exception\CannotDisconnect::CLASS);
        $this->pdo->disconnect();
    }

    public function testGetPdo()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->assertSame($this->innerPdo, $this->pdo->getPdo());
    }
}
