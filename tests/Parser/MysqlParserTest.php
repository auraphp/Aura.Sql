<?php
namespace Aura\Sql\Parser;

class MysqlParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $sql
     * @param array $parameters
     * @return Query
     */
    private function parseSingleQuery($sql, $parameters)
    {
        $parser = new MysqlParser();
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
        $parser = new MysqlParser();
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

        // The space character after the two -- is mandatory for MySQL
        $sql = "SELECT 1 --:foo";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $expectedStatement = "SELECT 1 --:foo, :foo_0";
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
        $this->assertEquals($expectedValues, $parsedQuery->getValues());
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

        // MySQL does not handle nested comments
        $sql = "SELECT
/* comment in
/* a comment
*/ :foo */
1";
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertNotEquals($sql, $parsedQuery->getStatement());
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
        $this->assertEquals($expectedValues, $parsedQuery->getValues());
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
SELECT 'single quote''s :foo'
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testIdentifierString()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT `:foo`
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());


        $sql = <<<SQL
SELECT `single quote``s :foo`
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());
    }

    public function testEscapedCharactersInStringConstants()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 'Escaping \' :foo \''
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT "Escaping \" :foo \""
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT "Escaping \\\\" :foo ""
SQL;
        $expectedStatement = <<<SQL
SELECT "Escaping \\\\" :foo, :foo_0 ""
SQL;
        $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($expectedStatement, $parsedQuery->getStatement());
        $this->assertEquals($expectedValues, $parsedQuery->getValues());

        $sql = <<<SQL
SELECT "Escaping \" :foo \"
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT "Escaping "" :foo """
SQL;
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($sql, $parsedQuery->getStatement());

        $sql = <<<SQL
SELECT 'Escaping '' :foo '''
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

    public function testIssue107()
    {
        $sql = "UPDATE table SET `value`=:value, `blank`='', `value2` = :value2, `blank2` = '', `value3`=:value3 WHERE id = :id";
        $parameters = ['value'=> 'string', 'id'=> 1, 'value2'=> 'string', 'value3'=>'string'];
        $parsedQuery = $this->parseSingleQuery($sql, $parameters);
        $this->assertEquals($parsedQuery->getStatement(), $sql);
        $this->assertEquals($parsedQuery->getValues(), $parameters);
    }
}
