<?php
namespace Aura\Sql;

use PDO;
use stdClass;

class DecoratedPdoTest extends ExtendedPdoTest
{
    protected function newPdo()
    {
        return new DecoratedPdo(new PDO('sqlite::memory:'));
    }


    public function testDisconnect()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->setExpectedException(Exception\CannotDisconnect::CLASS);
        $this->pdo->disconnect();
    }
}
