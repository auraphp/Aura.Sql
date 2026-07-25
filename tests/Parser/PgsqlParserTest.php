<?php
namespace Aura\Sql\Parser;

class PgsqlParserTest extends AbstractParserTest
{
    protected function setUp(): void
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

    /**
     * @see https://github.com/auraphp/Aura.Sql/issues/177
     *
     * A named array placeholder that appears after a type cast (`::`) which
     * immediately follows a string literal must still be expanded. Previously
     * the query part beginning with `::` was skipped entirely, so any
     * placeholder later in that same part was left untouched.
     */
    public function testArrayPlaceholderAfterTypeCast()
    {
        $parameters = ['types' => [1, 2]];
        $sql = "SELECT id FROM table WHERE removed = false"
             . " AND data @> '{\"is_hidden\":false}'::jsonb"
             . " AND type IN (:types)";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT id FROM table WHERE removed = false"
             . " AND data @> '{\"is_hidden\":false}'::jsonb"
             . " AND type IN (:types_0, :types_1)";
        $expectedValues = ['types_0' => 1, 'types_1' => 2];
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }

    /**
     * @see https://github.com/auraphp/Aura.Sql/issues/177
     *
     * The reordered query from the issue discussion worked before the fix;
     * make sure it keeps working afterwards.
     */
    public function testArrayPlaceholderBeforeTypeCast()
    {
        $parameters = ['types' => [1, 2]];
        $sql = "SELECT id FROM table WHERE type IN (:types)"
             . " AND removed = false"
             . " AND data @> '{\"is_hidden\":false}'::jsonb";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT id FROM table WHERE type IN (:types_0, :types_1)"
             . " AND removed = false"
             . " AND data @> '{\"is_hidden\":false}'::jsonb";
        $expectedValues = ['types_0' => 1, 'types_1' => 2];
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }

    /**
     * A scalar placeholder following a `::` cast must also survive.
     */
    public function testScalarPlaceholderAfterTypeCast()
    {
        $parameters = ['id' => 42];
        $sql = "SELECT 'hello'::TEXT AS greeting WHERE id = :id";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT 'hello'::TEXT AS greeting WHERE id = :id";
        $expectedValues = ['id' => 42];
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals($expectedValues, $values);
    }
}
