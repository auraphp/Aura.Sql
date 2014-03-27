<?php
namespace Aura\Sql;

use PDO;

class DecoratedPdoTest extends AbstractExtendedPdoTest
{
    protected $decorated_pdo;

    protected function newExtendedPdo()
    {
        $this->decorated_pdo = new PDO('sqlite::memory:');
        return new ExtendedPdo($this->decorated_pdo);
    }

    public function testGetPdo()
    {
        $this->assertSame($this->decorated_pdo, $this->pdo->getPdo());
    }
}
