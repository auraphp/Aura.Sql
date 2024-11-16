<?php

use Aura\Sql\ExtendedPdo;

class ExtendedConnectPdoTest extends \Aura\Sql\ExtendedPdoTest
{
    protected function newPdo()
    {
        return ExtendedPdo::connect('sqlite::memory:');
    }
}