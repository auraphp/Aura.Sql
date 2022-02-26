<?php
namespace Aura\Sql\Parser;

class SqliteParserTest extends AbstractParserTest
{
    protected function setUp(): void
    {
        $this->parser = new SqliteParser();
    }

    public function testIssue183()
    {
        $parameters = ['d' => 'foo'];
        $sql = "SELECT \"a:A\", 'B:b', `c:c`, :d FROM table";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT \"a:A\", 'B:b', `c:c`, :d FROM table";
        $expectedValues = ['d' => 'foo'];
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }
}
