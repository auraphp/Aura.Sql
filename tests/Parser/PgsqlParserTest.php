<?php
namespace Aura\Sql\Parser;

class PgsqlParserTest extends AbstractParserTest
{
    protected function setUp()
    {
        $this->parser = new PgsqlParser();
    }

    public function testUnicodeDoubleQuotedIdentifier()
    {
        $parameters = array('a000' => array('foo', 'bar'));
        $sql = <<<SQL
SELECT U&"\a000"
FROM (SELECT 1 AS U&":a000" UEScAPE ':') AS temp
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testCStyleStringConstants()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT E'C-style escaping \' :foo \''
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = <<<SQL
SELECT E'Multiline'
       'C-style escaping \' :foo \' :foo'
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testDollarQuotedStrings()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = 'SELECT $$:foo$$';
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = 'SELECT $tag$ :foo $tag$';
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = 'SELECT $outer$ nested strings $inner$:foo$inner$ $outer$';
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = 'SELECT $€$hello$€$';
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = 'SELECT $€$hello$€';
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testTypeCasting()
    {
        $parameters = array('TEXT' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 'hello'::TEXT
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
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
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testInvalidPlaceholderName()
    {
        $parameters = array(']' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 'hello':]
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }
}
