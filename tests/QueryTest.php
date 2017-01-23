<?php
namespace Aura\Sql\Parser;

use Aura\Sql\Query;

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
