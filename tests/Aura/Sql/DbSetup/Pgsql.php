<?php
namespace Aura\Sql\DbSetup;

class Pgsql extends AbstractDbSetup
{
    protected $create_table = "CREATE TABLE aura_test_table (
         id                     SERIAL PRIMARY KEY
        ,name                   VARCHAR(50) NOT NULL
        ,test_size_scale        NUMERIC(7,3)
        ,test_default_null      CHAR(3) DEFAULT NULL
        ,test_default_string    VARCHAR(7) DEFAULT 'string'
        ,test_default_number    NUMERIC(5) DEFAULT 12345
        ,test_default_ignore    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    protected function createSchemas()
    {
        $this->connection->query("CREATE SCHEMA aura_test_schema1");
        $this->connection->query("CREATE SCHEMA aura_test_schema2");
        $this->connection->query("SET search_path TO aura_test_schema1");
    }
    
    protected function dropSchemas()
    {
        $this->connection->query("DROP SCHEMA IF EXISTS aura_test_schema1 CASCADE");
        $this->connection->query("DROP SCHEMA IF EXISTS aura_test_schema2 CASCADE");
    }
    
}