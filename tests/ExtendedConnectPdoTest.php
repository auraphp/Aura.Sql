<?php

use Aura\Sql\ExtendedPdo;

class ExtendedConnectPdoTest extends \Aura\Sql\ExtendedPdoTest
{
    public function setUp(): void
    {
        if (PHP_VERSION_ID < 80400) {
            $this->markTestSkipped('PHP 8.3 and earlier do not support the new connect method.');
        }
        parent::setUp();
    }
    
    protected function newPdo()
    {
        // PHP 8.4+ has a new connect method to get a driver-specific subclass
        return ExtendedPdo::connect('sqlite::memory:');
    }
}