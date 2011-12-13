<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Connection;

/**
 * 
 * MySql Adapter
 * 
 * @package Aura.Sql
 * 
 */
use Aura\Sql\Select;
class Mysql extends AbstractConnection
{
    protected $dsn_prefix = 'mysql';
    
    protected $dsn = array(
        'host' => null,
        'port' => null,
        'dbname' => null,
        'unix_socket' => null,
        'charset' => null,
    );
    
    protected $ident_quote_prefix = '`';
    
    protected $ident_quote_suffix = '`';
    
    public function fetchTableList()
    {
        $text = 'SHOW TABLES';
        // if ($schema) {
        //     $text .= ' IN ' . $this->_quoteName($schema);
        // }
        return $this->fetchCol($text);
    }
    
    public function fetchTableCols($table)
    {
        $table = $this->quoteName($table);
        $text = "SHOW COLUMNS FROM $table";
        
        // get the column descriptions
        $raw_cols = $this->fetchAll($text);
        
        // where the column info will be stored
        $cols = array();
        
        // loop through the result rows; each describes a column.
        foreach ($raw_cols as $val) {
            
            $name = $val['Field'];
            list($type, $size, $scope) = $this->getTypeSizeScope($val['Type']);
            
            // save the column description
            $cols[$name] = array(
                'name'    => $name,
                'type'    => $type,
                'size'    => ($size  ? (int) $size  : null),
                'scope'   => ($scope ? (int) $scope : null),
                'default' => $val['Default'],
                'require' => (bool) ($val['Null'] != 'YES'),
                'primary' => (bool) ($val['Key'] == 'PRI'),
                'autoinc' => (bool) (strpos($val['Extra'], 'auto_increment') !== false),
            );
        }
        
        // done!
        return $cols;
    }
}
