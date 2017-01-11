<?php


namespace Aura\Sql;


class BaseParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInfiniteLoop()
    {
        $parser = new LoopParser();
        $query = new Query('SELECT *');
        $this->setExpectedException('Exception');
        $parser->normalize($query);
    }
}