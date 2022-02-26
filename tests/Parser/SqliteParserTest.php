<?php
namespace Aura\Sql\Parser;

class SqliteParserTest extends AbstractParserTest
{
    protected function set_up()
    {
        $this->parser = new SqliteParser();
    }
}
