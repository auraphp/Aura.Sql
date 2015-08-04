<?php
namespace Aura\Sql;

use Aura\Sql\PostgresRebuilder;

class PgsqlPdoTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        return new ExtendedPdo(new FakePgsqlPDO('sqlite::memory:'));
    }

    public function testGetPdo()
    {
        $lazy_pdo = $this->pdo->getPdo();
        $this->assertInstanceOf('PDO', $lazy_pdo);
        $this->assertNotSame($this->pdo, $lazy_pdo);
    }

    public function testGetPostgresBuilder()
    {
        $builder = $this->pdo->getRebuilder();
        $this->assertInstanceOf('Aura\Sql\PostgresRebuilder', $builder);
    }
}