<?php
namespace Aura\Sql;

use PDO;

class ExtendedPdoInjectTest extends AbstractExtendedPdoTest
{
    public function setUp()
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Need 'pdo_sqlite' to test in memory.");
        }

        $dsn = 'sqlite::memory:';
        $username = null;
        $password = null;
        $driver_options = array();
        
        // do this to test constructor array loop
        $attributes = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            $driver_options
        );

        $this->pdo = new ExtendedPdo($pdo);

        $this->createTable();
        $this->fillTable();
    }
}