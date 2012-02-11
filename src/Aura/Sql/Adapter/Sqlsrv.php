<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Adapter;

/**
 * 
 * Microsoft SQL Server adapter.
 * 
 * @package Aura.Sql
 * 
 */
class Sqlsrv extends AbstractAdapter
{
    protected $dsn_prefix = 'sqlsrv';
    
    protected $dsn = [
        'Server' => null,
        'Database' => null,
    ];
    
    protected $quote_name_prefix = '[';
    
    protected $quote_name_suffix = ']';
    
    /**
     * 
     * Returns a list of database tables.
     * 
     * @return array The list of tables in the database.
     * 
     */
    public function fetchTableList($schema = null)
    {
        $text = "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
        return $this->fetchCol($text);
    }
    
    public function fetchTableCols($spec)
    {
        list($schema, $table) = $this->splitIdent($spec);
        
        // get column info
        $text = "exec sp_columns @table_name = " . $this->quoteName($table);
        $raw_cols = $this->fetchAll($text);
        
        // get primary key info
        $text = "exec sp_pkeys @table_owner = " . $raw_cols[0]['TABLE_OWNER']
              . ", @table_name = " . $this->quoteName($table);
        $raw_keys = $this->fetchAll($text);
        $keys = [];
        foreach ($raw_keys as $row) {
            $keys[] = $row['COLUMN_NAME'];
        }
        
        $cols = [];
        foreach ($raw_cols as $row) {
            
            $name = $row['COLUMN_NAME'];
            
            $pos = strpos($row['TYPE_NAME'], ' ');
            if ($pos === false) {
                $type = $row['TYPE_NAME'];
            } else {
                $type = substr($row['TYPE_NAME'], 0, $pos);
            }
            
            // save the column description
            $cols[$name] = $this->column_factory->newInstance(
                $name,
                $type,
                $row['PRECISION'],
                $row['SCALE'],
                ! $row['NULLABLE'],
                $row['COLUMN_DEF'],
                strpos(strtolower($row['TYPE_NAME']), 'identity') !== false,
                in_array($name, $keys)
            );
        }
        
        return $cols;
    }
    
}
