<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Connection;

/**
 * 
 * Sqlsrv adapter
 * 
 */
use Aura\Sql\Select;
class Sqlsrv extends AbstractConnection
{
    protected $dsn_prefix = 'sqlsrv';
    
    protected $dsn = array(
        'Server' => null,
        'Database' => null,
    );
    
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
        $keys = array();
        foreach ($raw_keys as $row) {
            $keys[] = $row['COLUMN_NAME'];
        }
        
        $cols = array();
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
            $cols[$name]['require'] = ! $row['NULLABLE'];
            $cols[$name]['primary'] = in_array($name, $keys);
            $cols[$name]['autoinc'] = strpos(strtolower($row['TYPE_NAME']), 'identity') !== false;
        }
        
        return $cols;
    }
    
    public function convertSelect(Select $select)
    {
        $limit  = $select->limit;
        $offset = $select->offset;
        
        if (! $limit && ! $offset) {
            // no limit/offset so we can leave it as-is
            return $select->__toString();
        }
        
        if ($limit && ! $offset) {
            // limit, but no offset, so we can use TOP
            $text = $select->__toString();
            $text = preg_replace('/^(SELECT( DISTINCT)?)/', "$1 TOP $limit", $text);
            return $text;
        }
        
        return $this->convertSelectStrategy($select);
    }
    
    protected function convertSelectStrategy(Select $select)
    {
        // limit and offset. a little complicated.
        // first, get the existing order as a string, then remove it.
        $order = $select->getOrderString();
        $select->clearOrder();
        
        // we always need an order for the ROW_NUMBER() OVER(...)
        if (! $order) {
            // always need an order
            $order = '(SELECT 1)';
        }
        
        $start = $select->offset + 1;
        $end   = $select->offset + $select->limit;
        
        return "WITH outertable AS (SELECT *, ROW_NUMBER() OVER (ORDER BY $order) AS __rownum__ FROM (\n"
             . $select->__toString()
             . "\n) AS innertable) SELECT * FROM outertable WHERE __rownum__ BETWEEN $start AND $end";
    }
}
