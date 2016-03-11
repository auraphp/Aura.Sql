<?php
namespace Aura\Sql;

use PDO;

class DecoratedPdoTest extends AbstractExtendedPdoTest
{
    protected $underlying_pdo;

    protected function newExtendedPdo()
    {
        $this->underlying_pdo = new PDO('sqlite::memory:');
        return new DecoratedPdo($this->underlying_pdo);
    }

    public function testGetPdo()
    {
        $this->assertSame($this->underlying_pdo, $this->pdo->getPdo());
    }

    public function testDisconnect()
    {
        $this->markTestSkipped('Disconnect/reconnect semantics have changed.');
        $this->setExpectedException(
            'Aura\Sql\Exception\CannotDisconnect',
            'Cannot disconnect an injected PDO instance.'
        );
        $this->pdo->disconnect();
    }
}
