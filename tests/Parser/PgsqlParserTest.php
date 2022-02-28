<?php
namespace Aura\Sql\Parser;

class PgsqlParserTest extends AbstractParserTest
{
    protected function set_up()
    {
        $this->parser = new PgsqlParser();
    }

    public function testUnicodeDoubleQuotedIdentifier()
    {
        $parameters = ['a000' => ['foo', 'bar']];
        $sql = <<<SQL
SELECT U&"\a000"
FROM (SELECT 1 AS U&":a000" UEScAPE ':') AS temp
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testCStyleStringConstants()
    {
        $parameters = ['foo' => ['bar', 'baz']];
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
        $parameters = ['foo' => ['bar', 'baz']];
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
        $parameters = ['TEXT' => ['bar', 'baz']];
        $sql = <<<SQL
SELECT 'hello'::TEXT
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testArrayAccessor()
    {
        $parameters = ['2' => ['bar', 'baz']];
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
        $parameters = [']' => ['bar', 'baz']];
        $sql = <<<SQL
SELECT 'hello':]
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    public function testIssue177()
    {
        $parameters = ['types' => [1, 2]];
        //  AND
        $sql = <<<SQL
SELECT * FROM table WHERE type in (:types) AND removed = false AND data @> '{\"is_hidden\":false}'::jsonb
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);

        $expectedSql = <<<SQL
SELECT * FROM table WHERE type in (:types_0, :types_1) AND removed = false AND data @> '{\"is_hidden\":false}'::jsonb
SQL;

        $expectedParameters = [
            'types_0' => 1,
            'types_1' => 2
        ];

        $this->assertEquals($expectedSql, $statement);
        $this->assertEquals($expectedParameters, $values);

        // change order
        $sql = <<<SQL
SELECT * FROM table WHERE removed = false AND data @> '{\"is_hidden\":false}'::jsonb  AND type in (:types)
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);

        $expectedSql = <<<SQL
SELECT * FROM table WHERE removed = false AND data @> '{\"is_hidden\":false}'::jsonb AND type in (:types_0, :types_1)
SQL;

        $this->assertEquals($expectedSql, $statement);
        $this->assertEquals($expectedParameters, $values);
    }
}
