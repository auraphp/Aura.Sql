<?php
namespace Aura\Sql\Parser;

class MysqlParserTest extends AbstractParserTest
{
    protected function setUp(): void
    {
        $this->parser = new MysqlParser();
    }

    public function testBacktickString()
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = <<<SQL
SELECT `:foo`
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);


        $sql = <<<SQL
SELECT `single quote``s :foo`
SQL;
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $this->assertEquals($sql, $statement);
    }

    /**
     * @see https://github.com/auraphp/Aura.Sql/issues/181
     *
     * Aura.Sql's parser only rebuilds bound placeholders; it never quotes
     * identifiers. Expressions such as system variables (`@@session.time_zone`)
     * and function calls with keywords (`COUNT(DISTINCT ...)`) must pass through
     * verbatim, with no backticks added. (Identifier quoting reported in the
     * issue belongs to the separate Aura.SqlQuery package.)
     */
    public function testDoesNotQuoteExpressions()
    {
        $parameters = ['tz' => 'UTC'];

        $sql = "SELECT CONVERT_TZ(open_from, customer.time_zone, @@session.time_zone) AS open_now"
             . " FROM customer WHERE customer.time_zone = :tz";
        list ($statement, $values) = $this->rebuild($sql, $parameters);
        $expectedStatement = "SELECT CONVERT_TZ(open_from, customer.time_zone, @@session.time_zone) AS open_now"
             . " FROM customer WHERE customer.time_zone = :tz";
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals(['tz' => 'UTC'], $values);

        $sql = "SELECT COUNT(DISTINCT customer_id) FROM orders WHERE status = :status";
        list ($statement, $values) = $this->rebuild($sql, ['status' => 'paid']);
        $expectedStatement = "SELECT COUNT(DISTINCT customer_id) FROM orders WHERE status = :status";
        $this->assertEquals($expectedStatement, $statement);
        $this->assertEquals(['status' => 'paid'], $values);
    }
}
