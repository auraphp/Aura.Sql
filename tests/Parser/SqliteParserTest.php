<?php
namespace Aura\Sql\Parser;

class SqliteParserTest extends AbstractParserTest
{
    protected function setUp(): void
    {
        $this->parser = new SqliteParser();
    }
}
