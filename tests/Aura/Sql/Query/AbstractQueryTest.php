<?php
namespace Aura\Sql\Query;

use Aura\Sql\ConnectionFactory;
use Aura\Sql\Query\Factory as QueryFactory;
use Aura\Sql\Assertions;

abstract class AbstractQueryTest extends \PHPUnit_Framework_TestCase
{
    use Assertions;
    
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

    public function testGetConnection()
    {
        $connection = $this->query->getConnection();
        $this->assertSame($this->connection, $connection);
    }
    
    public function testSetAddGetBind()
    {
        $actual = $this->query->getBind();
        $this->assertSame([], $actual);
        
        $expect = ['foo' => 'bar', 'baz' => 'dib'];
        $this->query->setBind($expect);
        $actual = $this->query->getBind();
        $this->assertSame($expect, $actual);
        
        $this->query->addBind(['zim' => 'gir']);
        $expect = ['foo' => 'bar', 'baz' => 'dib', 'zim' => 'gir'];
        $actual = $this->query->getBind();
        $this->assertSame($expect, $actual);
    }
}
