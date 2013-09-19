<?php
namespace Aura\Sql\DbSetup;

class Sqlite extends AbstractDbSetup
{
    protected $create_table = "CREATE TABLE aura_test_table (
         id                     INTEGER PRIMARY KEY AUTOINCREMENT
        ,name                   VARCHAR(50) NOT NULL
        ,test_size_scale        NUMERIC(7,3)
        ,test_default_null      CHAR(3) DEFAULT NULL
        ,test_default_string    VARCHAR(7) DEFAULT 'string'
        ,test_default_number    NUMERIC(5) DEFAULT 12345
        ,test_default_ignore    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    protected function createSchemas()
    {
        // only need to create the second one
        $this->connection->query("ATTACH DATABASE ':memory:' AS aura_test_schema2");
    }
    
    protected function dropSchemas()
    {
        // all in memory, no need to clean up
    }
}
