<?php
namespace Aura\Sql\Parser;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $sqlString = "SELECT 'foo'";
        $parameters = array('bar' => 'baz');
        $query = new Query($sqlString, $parameters);
        $this->assertEquals($sqlString, $query->getString());
        $this->assertEquals($parameters, $query->getParameters());
    }
}
