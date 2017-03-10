<?php
namespace Aura\Sql\Parser;

class MysqlParserTest extends AbstractParserTest
{
    protected function setUp()
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
}
