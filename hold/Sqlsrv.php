<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Driver;

/**
 * 
 * Sqlsrv adapter
 * 
 * @package Aura.Sql
 * 
 */
class Sqlsrv extends AbstractDriver
{
    protected $dsn_prefix = 'sqlsrv';
    
    protected $dsn = [
        'Server' => null,
        'Database' => null,
    ];
    
    protected $ident_quote_prefix = '[';
    
    protected $ident_quote_suffix = ']';
    
    /**
     * 
     * Returns a list of database tables.
     * 
     * @return array The list of tables in the database.
     * 
     */
    public function fetchTableList()
    {
        $text = "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
        return $this->fetchCol($text);
    }
    
    public function fetchTableCols($table)
    {
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
            
            $cols[$name]['name']    = $name;
            $cols[$name]['type']    = $type;
            $cols[$name]['size']    = $row['PRECISION'];
            $cols[$name]['scope']   = $row['SCALE'];
            $cols[$name]['default'] = $row['COLUMN_DEF'];
            $cols[$name]['notnull'] = ! $row['NULLABLE'];
            $cols[$name]['primary'] = in_array($name, $keys);
            $cols[$name]['autoinc'] = strpos(strtolower($row['TYPE_NAME']), 'identity') !== false;
        }
        
        return $cols;
    }
    
}
