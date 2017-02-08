<?php
namespace Aura\Sql\Parser;

class LoopParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException Aura\Sql\Exception\ParserLoop
     */
    public function testInfiniteLoop()
    {
        $parser = new LoopParser();
        $parser->rebuild('SELECT *');
    }
}
