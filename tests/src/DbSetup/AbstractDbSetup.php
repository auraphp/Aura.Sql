<?php
namespace Aura\Sql\DbSetup;

abstract class AbstractDbSetup
{
    public $connection;
    
    public $table;
    
    public function __construct($connection, $table, $schema1, $schema2)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->schema1 = $schema1;
        $this->schema2 = $schema2;
    }
    
    public function exec()
    {
        $this->dropSchemas();
        $this->createSchemas();
        $this->createTables();
        $this->fillTable();
    }
    
    abstract protected function createSchemas();
    
    abstract protected function dropSchemas();
    
    protected function createTables()
    {
        // create in schema 1
        $sql = $this->create_table;
        $this->connection->query($sql);
        
        // create again in schema 2
        $sql = str_replace($this->table, "{$this->schema2}.{$this->table}", $sql);
        $this->connection->query($sql);
    }
    
    // only fills in schema 1
    protected function fillTable()
    {
        $names = [
            'Anna', 'Betty', 'Clara', 'Donna', 'Fiona',
            'Gertrude', 'Hanna', 'Ione', 'Julia', 'Kara',
        ];
        
        foreach ($names as $name) {
            $this->connection->insert($this->table, ['name' => $name]);
        }
    }
    
}
