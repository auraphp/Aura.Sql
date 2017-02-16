<?php
namespace Aura\Sql\Parser;

class SqliteParserTest extends AbstractParserTest
{
    protected function setUp()
    {
        $this->parser = new SqliteParser();
    }
}
