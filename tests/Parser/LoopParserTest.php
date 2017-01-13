<?php
namespace Aura\Sql\Parser;

use Aura\Sql\Query;

class LoopParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInfiniteLoop()
    {
        $parser = new LoopParser();
        $query = new Query('SELECT *');
        $this->setExpectedException('Exception');
        $parser->rebuild($query);
    }
}
