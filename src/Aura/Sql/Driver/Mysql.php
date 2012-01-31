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
 * MySql Adapter
 * 
 * @package Aura.Sql
 * 
 */
class Mysql extends AbstractDriver
{
    protected $dsn_prefix = 'mysql';
    
    protected $dsn = [
        'host' => null,
        'port' => null,
        'dbname' => null,
        'unix_socket' => null,
        'charset' => null,
    ];
    
    protected $ident_quote_prefix = '`';
    
    protected $ident_quote_suffix = '`';
    
    public function fetchTableList($schema = null)
    {
        $text = 'SHOW TABLES';
        if ($schema) {
            $text .= ' IN ' . $this->replaceName($schema);
        }
        return $this->fetchCol($text);
    }
    
    public function fetchTableCols($table, $schema = null)
    {
        $table = $this->quoteName($table);
        $text = "SHOW COLUMNS FROM $table";
        
        if ($schema) {
            $schema = preg_replace('/[^\w]/', '', $schema);
            $schema = $this->replaceName($schema);
            $text .= " IN $schema";
        }
        
        // get the column descriptions
        $raw_cols = $this->fetchAll($text);
        
        // where the column info will be stored
        $cols = [];
        
        // loop through the result rows; each describes a column.
        foreach ($raw_cols as $val) {
            
            $name = $val['Field'];
            list($type, $size, $scope) = $this->getTypeSizeScope($val['Type']);
            
            // save the column description
            $cols[$name] = [
                'name'    => $name,
                'type'    => $type,
                'size'    => ($size  ? (int) $size  : null),
                'scope'   => ($scope ? (int) $scope : null),
                'default' => $this->getDefault($val['Default']),
                'notnull' => (bool) ($val['Null'] != 'YES'),
                'primary' => (bool) ($val['Key'] == 'PRI'),
                'autoinc' => (bool) (strpos($val['Extra'], 'auto_increment') !== false),
            ];
        }
        
        // done!
        return $cols;
    }
    
    protected function getDefault($default)
    {
        $upper = strtoupper($default);
        if ($upper == 'NULL' || $upper == 'CURRENT_TIMESTAMP') {
            // the only non-literal allowed by MySQL is "CURRENT_TIMESTAMP"
            return null;
        } else {
            // return the literal default
            return $default;
        }
    }
}
