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
        $lazy_pdo = $this->pdo;
        $connected_pdo = $lazy_pdo->getPdo();
        $this->assertNotEquals(null, $connected_pdo);
        $lazy_pdo->disconnect();
        $this->assertEquals(null, $lazy_pdo->getPdo());
    }
}
