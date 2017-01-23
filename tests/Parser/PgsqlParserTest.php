<?php
namespace Aura\Sql\Parser;

class PgsqlParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $sql
     * @param array $parameters
     * @return Query
     */
    private function parseSingleQuery($sql, $parameters)
    {
        $parser = new PgsqlParser();
        $parsedQueries = $parser->rebuild($sql, $parameters);
        $this->assertTrue(count($parsedQueries) == 1);
        return $parsedQueries[0];
    }

    /**
     * @param $sql
     * @param $parameters
     * @return Query[]
     */
    private function parseMultipleQueries($sql, $parameters)
    {
        $parser = new PgsqlParser();
        $parsedQueries = $parser->rebuild($sql, $parameters);
        return $parsedQueries;
    }

    public function testReplaceMultipleUsesOfNamedParameter()
    {
        $parameters = array('foo' => 'bar');
        $sql = "SELECT :foo AS a, :foo AS b";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT :foo AS a, :foo_0 AS b";
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'bar');
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
        $this->assertEquals($expectedValues, $parsedQuery->getValues());
    }

    public function testReplaceNumberedParameter()
    {
        $parameters = array('bar', 'baz');
        $sql = "SELECT ? AS a, ? AS b";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT :__numbered AS a, :__numbered_0 AS b";
        $expectedValues = array('__numbered' => 'bar', '__numbered_0' => 'baz');
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
        $this->assertEquals($expectedValues, $parsedQuery->getValues());
    }

    public function testReplaceArrayAsParameter()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = "SELECT :foo";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT :foo, :foo_0";
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
        $this->assertEquals($expectedValues, $parsedQuery->getValues());

        $parameters = array(array('bar', 'baz'));
        $sql = "SELECT ?";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT :__numbered, :__numbered_0";
        $expectedValues = array('__numbered' => 'bar', '__numbered_0' => 'baz');
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
        $this->assertEquals($expectedValues, $parsedQuery->getValues());
    }

    public function testSingleLineComment()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = "-- :foo";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = "SELECT 1 -- :foo = 1";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = "SELECT 1
-- :foo";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = "SELECT 1 - :foo";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT 1 - :foo, :foo_0";
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
    }

    public function testMultiLineComment()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = "SELECT
/*
:foo
*/
1";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = "SELECT
/* comment in
/* a comment
*/ :foo */
1";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = "SELECT 1 / :foo";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT 1 / :foo, :foo_0";
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
    }

    public function testDoubleQuotedIdentifier()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT ":foo"
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT "to use double quotes, just double them "" :foo "
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $parameters = array('a000' => array('foo', 'bar'));
        $sql = <<<SQL
SELECT U&"\a000"
FROM (SELECT 1 AS U&":a000" UEScAPE ':') AS temp
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testStringConstants()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT ':foo'
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT 'multi line string'
':foo'
'bar'
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT 'single quote''s :foo'
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testCStyleStringConstants()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT E'C-style escaping \' :foo \''
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT E'Multiline'
'C-style escaping \' :foo \''
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testUnicodeStringConstants()
    {
        $parameters = array('b0a0' => array('foo', 'bar'), 'a000' => array('baz', 'qux'));
        $sql = <<<SQL
SELECT u&':a000'
'\'':b0a0' UeSCaPE ':'
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testDollarQuotedStrings()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = 'SELECT $$:foo$$';
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = 'SELECT $tag$ :foo $tag$';
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = 'SELECT $outer$ nested strings $inner$:foo$inner$ $outer$';
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testTypeCasting()
    {
        $parameters = array('TEXT' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 'hello'::TEXT
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testArrayAccessor()
    {
        $parameters = array('2' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT test[1:2]
FROM (
SELECT CAST('{"foo", "bar", "baz", "qux"}' AS TEXT[]) AS test
) AS t
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testMultiQueries()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 1;

SQL;
        $queries = $this->parseMultipleQueries($sql, $parameters);
        $this->assertTrue(count($queries) == 1);

        $sql = <<<SQL
SELECT 1;
SELECT 2

SQL;
        $queries = $this->parseMultipleQueries($sql, $parameters);
        $this->assertTrue(count($queries) == 2);

        $sql = <<<SQL
SELECT $$1; SELECT 2$$
SQL;
        $queries = $this->parseMultipleQueries($sql, $parameters);
        $this->assertTrue(count($queries) == 1);

        $sql = <<<SQL
SELECT "'a';SELECT 1" FROM (SELECT 1 AS "'a';SELECT 1") AS t
SQL;
        $queries = $this->parseMultipleQueries($sql, $parameters);
        $this->assertTrue(count($queries) == 1);

        $sql = <<<SQL
SELECT :foo;
SELECT :foo
SQL;
        $queries = $this->parseMultipleQueries($sql, $parameters);
        $this->assertTrue(count($queries) == 2);
        $expectedStatement = "SELECT :foo, :foo_0";
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
        $this->assertEquals($expectedStatement, $queries[0]->getStatement());
        $this->assertEquals($expectedStatement, $queries[1]->getStatement());
        $this->assertEquals($expectedValues, $queries[0]->getValues());
        $this->assertEquals($expectedValues, $queries[1]->getValues());

        $parameters = array('foo' => array('bar', 'baz'), 'bar' => array('foo', 'qux'));
        $sql = <<<SQL
SELECT :bar;
SELECT :foo
SQL;
        $queries = $this->parseMultipleQueries($sql, $parameters);
        $this->assertTrue(count($queries) == 2);
        $expectedStatement = "SELECT :bar, :bar_0";
        $expectedValues = array('bar' => 'foo', 'bar_0' => 'qux');
        $this->assertEquals($expectedStatement, $queries[0]->getStatement());
        $this->assertEquals($expectedValues, $queries[0]->getValues());
        $expectedStatement = "SELECT :foo, :foo_0";
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
        $this->assertEquals($expectedStatement, $queries[1]->getStatement());
        $this->assertEquals($expectedValues, $queries[1]->getValues());
    }

    public function testInvalidPlaceholderName()
    {
        $parameters = array(']' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 'hello':]
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }
}