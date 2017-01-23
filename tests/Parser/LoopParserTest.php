<?php
namespace Aura\Sql\Parser;

class LoopParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInfiniteLoop()
    {
        $parser = new LoopParser();
        $this->setExpectedException('Aura\Sql\Exception\ParserLoop');
        $parser->rebuild('SELECT *');
    }
}
