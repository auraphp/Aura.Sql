<?php
namespace Aura\Sql\Parser;

class NullParserTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $parser = new NullParser();
        list ($statement, $values) = $parser->rebuild('foo', ['bar' => 'baz']);
        $this->assertSame('foo', $statement);
        $this->assertSame(['bar' => 'baz'], $values);
    }
}
