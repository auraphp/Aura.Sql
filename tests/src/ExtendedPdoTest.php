<?php
namespace Aura\Sql;

use PDO;

class ExtendedPdoTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        $dsn = 'sqlite::memory:';
        $username = null;
        $password = null;
        $driver_options = array();
        
        // do this to test constructor array loop
        $attributes = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        
        return new ExtendedPdo(
            $dsn,
            $username,
            $password,
            $driver_options,
            $attributes
        );
    }

    public function testGetPdo()
    {
        $lazy_pdo = $this->pdo->getPdo();
        $this->assertInstanceOf('PDO', $lazy_pdo);
        $this->assertNotSame($this->pdo, $lazy_pdo);
    }
}
