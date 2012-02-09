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
 * SQLite driver.
 * 
 * @package Aura.Sql
 * 
 */
class Sqlite extends AbstractDriver
{
    /**
     * 
     * The string used for SQLite autoincrement data types.
     * 
     * This is different for versions 2 and 3 of SQLite.
     * 
     * @var string
     * 
     */
    protected $autoinc_string = 'INTEGER PRIMARY KEY AUTOINCREMENT';
    
    /**
     * 
     * The PDO DSN for the connection, typically a file path.
     * 
     * @var string
     * 
     */
    protected $dsn = null;
    
    /**
     * 
     * The PDO type prefix.
     * 
     * @var string
     * 
     */
    protected $dsn_prefix = 'sqlite';
    
    /**
     * 
     * The quote character before an entity name (table, index, etc).
     * 
     * @var string
     * 
     */
    protected $quote_name_prefix = '"';
    
    /**
     * 
     * The quote character after an entity name (table, index, etc).
     * 
     * @var string
     * 
     */
    protected $quote_name_suffix = '"';
    
    /**
     * 
     * Returns an list of tables in the database.
     * 
     * @param string $schema Optionally, pass a schema name to get the list
     * of tables in this schema.
     * 
     * @return array The list of tables in the database.
     * 
     */
    public function fetchTableList($schema = null)
    {
        if ($schema) {
            $cmd = "
                SELECT name FROM {$schema}.sqlite_master WHERE type = 'table'
                ORDER BY name
            ";
        } else {
            $cmd = "
                SELECT name FROM sqlite_master WHERE type = 'table'
                UNION ALL
                SELECT name FROM sqlite_temp_master WHERE type = 'table'
                ORDER BY name
            ";
        }
        
        return $this->fetchCol($cmd);
    }
    
    /**
     * 
     * Describes the columns in a table.
     * 
     * @param string $table The table name to fetch columns for.
     * 
     * @param string $schema The attached database in which the table resides.
     * 
     * @return array
     * 
     */
    public function fetchTableCols($table, $schema = null)
    {
        // sqlite> create table areas (id INTEGER PRIMARY KEY AUTOINCREMENT,
        //         name VARCHAR(32) NOT NULL);
        // sqlite> pragma table_info(areas);
        // cid |name |type        |notnull |dflt_value |pk
        // 0   |id   |INTEGER     |0       |           |1
        // 1   |name |VARCHAR(32) |99      |           |0
        
        // strip non-word characters to try and prevent SQL injections
        $table = preg_replace('/[^\w]/', '', $table);
        
        // is there a schema?
        if ($schema) {
            // sanitize and add a dot
            $schema = preg_replace('/[^\w]/', '', $schema) . '.';
        }
        
        // where the description will be stored
        $cols = array();
        
        // get the CREATE TABLE sql; need this for finding autoincrement cols
        $cmd = "
            SELECT sql FROM {$schema}sqlite_master
            WHERE type = 'table' AND name = :table
        ";
        $create_table = $this->fetchValue($cmd, array('table' => $table));
        
        // get the column descriptions
        $table = $this->quoteName($table);
        $raw_cols = $this->fetchAll("PRAGMA {$schema}TABLE_INFO($table)");
        
        // loop through the result rows; each describes a column.
        foreach ($raw_cols as $val) {
            $name = $val['name'];
            list($type, $size, $scale) = $this->getTypeSizeScope($val['type']);
            
            // find autoincrement column in CREATE TABLE sql.
            $autoinc_find = str_replace(' ', '\s+', $this->autoinc_string);
            $find = "(\"$name\"|\'$name\'|`$name`|\[$name\]|\\b$name)" 
                  . "\s+$autoinc_find";
            
            $autoinc = preg_match(
                "/$find/Ui",
                $create_table,
                $matches
            );
            
            $default = null;
            if ($val['dflt_value'] && $val['dflt_value'] != 'NULL') {
                $default = trim($val['dflt_value'], "'");
            }
            
            $cols[$name] = array(
                'name'    => $name,
                'type'    => $type,
                'size'    => ($size  ? (int) $size  : null),
                'scale'   => ($scale ? (int) $scale : null),
                'default' => $default,
                'notnull' => (bool) ($val['notnull']),
                'primary' => (bool) ($val['pk'] == 1),
                'autoinc' => (bool) $autoinc,
            );
        }
        
        // For defaults using keywords, SQLite always reports the keyword
        // *value*, not the keyword itself (e.g., '2007-03-07' instead of
        // 'CURRENT_DATE').
        // 
        // The allowed keywords are CURRENT_DATE, CURRENT_TIME, and
        // CURRENT_TIMESTAMP.
        // 
        //   <http://www.sqlite.org/lang_createtable.html>
        // 
        // Check the table-creation SQL for the default value to see if it's
        // a keyword and report 'null' in those cases.
        
        // get the list of column names
        $names = array_keys($cols);
        
        // how many are there?
        $last = count($names) - 1;
        
        // loop through each column and find out if its default is a keyword
        foreach ($names as $curr => $name) {
            
            // if there is a default value ...
            if ($cols[$name]['default']) {
            
                // look for :curr_col :curr_type . DEFAULT CURRENT_(*)
                $find = $cols[$name]['name'] . '\s+'
                      . $cols[$name]['type']
                      . '.*\s+DEFAULT\s+CURRENT_';
                
                // if not at the end, don't look further than the next coldef
                if ($curr < $last) {
                    $next = $names[$curr + 1];
                    $find .= '.*' . $cols[$next]['name'] . '\s+'
                           . $cols[$next]['type'];
                }
                
                // is the default a keyword?
                preg_match("/$find/ims", $create_table, $matches);
                if (! empty($matches)) {
                    $cols[$name]['default'] = null;
                }
            }
            
            // convert to a column object
            $cols[$name] = $this->column_factory->newInstance(
                $cols[$name]['name'],
                $cols[$name]['type'],
                $cols[$name]['size'],
                $cols[$name]['scale'],
                $cols[$name]['notnull'],
                $cols[$name]['default'],
                $cols[$name]['autoinc'],
                $cols[$name]['primary']
            );
        }
        
        // done!
        return $cols;
    }
    
    /**
     * 
     * Returns the last ID inserted on the connection.
     * 
     * @return mixed
     * 
     */
    public function lastInsertId()
    {
        $pdo = $this->getPdo();
        return $pdo->lastInsertId();
    }
}
