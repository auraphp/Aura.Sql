<?php
namespace Aura\Sql;

class ExtendedPdoTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        return new ExtendedPdo('sqlite::memory:');
    }

    public function testGetPdo()
    {
        $this->assertSame($this->pdo, $this->pdo->getPdo());
    }
}
