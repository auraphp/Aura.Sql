<?php
namespace Aura\Sql\Query;

use Aura\Sql\ConnectionFactory;
use Aura\Sql\Query\Factory as QueryFactory;

abstract class AbstractQueryTest extends \PHPUnit_Framework_TestCase
{
    protected $query_type;
    
    protected $query;

    protected $connection;
    
    protected function setUp()
    {
        parent::setUp();
        $connection_factory = new ConnectionFactory;
        $query_factory   = new QueryFactory;
        $this->connection   = $connection_factory->newInstance('sqlite', ':memory:');
        $this->query     = $query_factory->newInstance(
            $this->query_type,
            $this->connection
        );
    }
    
    protected function tearDown()
    {
        parent::tearDown();
    }

    protected function assertSameSql($expect, $actual)
    {
        $expect = trim($expect);
        $expect = preg_replace('/^\s*/m', '', $expect);
        $expect = preg_replace('/\s*$/m', '', $expect);
        
        $actual = trim($actual);
        $actual = preg_replace('/^\s*/m', '', $actual);
        $actual = preg_replace('/\s*$/m', '', $actual);
        
        $this->assertSame($expect, $actual);
    }
}
