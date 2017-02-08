<?php
namespace Aura\Sql\Parser;

class QueryTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $sqlString = "SELECT 'foo'";
        $parameters = array('bar' => 'baz');
        $query = new Query($sqlString, $parameters);
        $this->assertEquals($sqlString, $query->getStatement());
        $this->assertEquals($parameters, $query->getValues());
    }
}
