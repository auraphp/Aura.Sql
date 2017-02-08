<?php
namespace Aura\Sql\Parser;

class NullParserTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $parser = new NullParser();
        $parsedQueries = $parser->rebuild('foo');
        $this->assertTrue(count($parsedQueries) == 1);
        $this->assertSame('foo', $parsedQueries[0]->getStatement());
        $this->assertSame([], $parsedQueries[0]->getValues());
    }
}
