<?php
namespace Aura\Sql\Parser;

abstract class AbstractParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    protected function rebuild($sql, $parameters)
    {
        $parser = clone $this->parser;
        return $parser->rebuild($sql, $parameters);
    }

    public function testReplaceMultipleUsesOfNamedParameter()
    {
        $parameters = array('foo' => 'bar');
        $sql = "SELECT :foo AS a, :foo AS b";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT :foo AS a, :foo__1 AS b";
        $expectedValues = array('foo' => 'bar', 'foo__1' => 'bar');
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }

    public function testReplaceNumberedParameter()
    {
        $parameters = array('bar', 'baz');
        $sql = "SELECT ? AS a, ? AS b";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT :__1 AS a, :__2 AS b";
        $expectedValues = array('__1' => 'bar', '__2' => 'baz');
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }

    public function testReplaceArrayAsParameter()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = "SELECT :foo";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT :foo_0, :foo_1";
        $expectedValues = array('foo_0' => 'bar', 'foo_1' => 'baz');
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);

        $parameters = array(array('bar', 'baz'));
        $sql = "SELECT ?";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT :__1, :__2";
        $expectedValues = array('__1' => 'bar', '__2' => 'baz');
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }

    public function testDoubleQuotedIdentifier()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT ":foo"
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = <<<SQL
SELECT "to use double quotes, just double them "" :foo "
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testStringConstants()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT ':foo'
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);


        $sql = <<<SQL
SELECT 'single quote''s :foo'
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = <<<SQL
SELECT 'multi line string'
':foo'
'bar'
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testEscapedCharactersInStringConstants()
    {
        $parameters = array('foo' => array('bar', 'baz'));
        $sql = <<<SQL
SELECT 'Escaping \' :foo \''
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = <<< 'SQL'
SELECT "Escaping \" :foo \""
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

//         $sql = <<<SQL
// SELECT "Escaping \\\\" :foo ""
// SQL;
//         $expectedStatement = <<<SQL
// SELECT "Escaping \\\\" :foo, :foo_0 ""
// SQL;
//         $expectedValues = array('foo' => 'bar', 'foo_0' => 'baz');
//         list ($statement, $values) = $this->rebuild($sql, $parameters);
//         $this->assertEquals($expectedStatement, $statement);
//         $this->assertEquals($expectedValues, $values);

        $sql = <<<SQL
SELECT "Escaping \" :foo \"
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = <<<SQL
SELECT "Escaping "" :foo """
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);

        $sql = <<<SQL
SELECT 'Escaping '' :foo '''
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testIssue107()
    {
        $sql = "UPDATE table SET `value`=:value, `blank`='', `value2` = :value2, `blank2` = '', `value3`=:value3 WHERE id = :id";
        $parameters = ['value'=> 'string', 'id'=> 1, 'value2'=> 'string', 'value3'=>'string'];
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($statement, $sql);
        $this->assertEquals($values, $parameters);
    }
}
