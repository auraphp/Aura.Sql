<?php
namespace Aura\Sql\Rebuilder;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $sqlString = "SELECT 'foo'";
        $parameters = array('bar' => 'baz');
        $query = new Query($sqlString, $parameters);
        $this->assertEquals($sqlString, $query->getStatement());
        $this->assertEquals($parameters, $query->getAllValues());
        $this->assertEquals([], $query->getUsedValues());
    }
}
