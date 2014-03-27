<?php
namespace Aura\Sql;

use PDO;

class DecoratedPdoTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        return new ExtendedPdo($pdo);
    }
}
