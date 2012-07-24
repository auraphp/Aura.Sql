<?php
namespace Aura\Sql\Query;

use Aura\Sql\AdapterFactory;
use Aura\Sql\Query\Factory as QueryFactory;

abstract class AbstractQueryTest extends \PHPUnit_Framework_TestCase
{
    protected $query_type;
    
    protected $query;

    protected $adapter;
    
    protected function setUp()
    {
        parent::setUp();
        $adapter_factory = new AdapterFactory;
        $query_factory   = new QueryFactory;
        $this->adapter   = $adapter_factory->newInstance('sqlite', ':memory:');
        $this->query     = $query_factory->newInstance(
            $this->query_type,
            $this->adapter
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
    
    /**
     * @expectedException Aura\Sql\Exception\NoSuchQueryType
     */
    public function testNoSuchQueryTypeException()
    {
        $query_factory   = new QueryFactory;
        $this->query     = $query_factory->newInstance(
            'somestype',
            $this->adapter
        );
    }
}
