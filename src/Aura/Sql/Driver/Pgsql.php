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
 * PostgreSQL driver.
 * 
 * @package Aura.Sql
 * 
 */
class Pgsql extends AbstractDriver
{
    /**
     * 
     * The PDO DSN for the connection. This can be an array of key-value pairs
     * or a string (minus the PDO type prefix).
     * 
     * @var string|array
     * 
     */
    protected $dsn = [
        'host' => null,
        'port' => null,
        'dbname' => null,
        'user' => null,
        'password' => null,
    ];
    
    /**
     * 
     * The PDO type prefix.
     * 
     * @var string
     * 
     */
    protected $dsn_prefix = 'pgsql';
    
    /**
     * 
     * The prefix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_prefix = '"';
    
    /**
     * 
     * The suffix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_suffix = '"';
    
    /**
     * 
     * Returns a list of all tables in the database.
     * 
     * @param string $schema Fetch tbe list of tables in this schema; 
     * when empty, uses the default schema.
     * 
     * @return array All table names in the database.
     * 
     */
    public function fetchTableList($schema = null)
    {
        if ($schema) {
            $cmd = "
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = :schema
            ";
        } else {
            $cmd = "
                SELECT table_schema || '.' || table_name
                FROM information_schema.tables
                WHERE table_schema != 'pg_catalog'
                AND table_schema != 'information_schema'
            ";
        }
        
        return $this->fetchCol($cmd, array('schema' => $schema));
    }
    
    /**
     * 
     * Returns an array of columns in a table.
     * 
     * @param string $table Return the columns in this table.
     * 
     * @param string $schema Optionally, look for the table in this schema.
     * 
     * @return array An associative array where the key is the column name
     * and the value is an array describing the column.
     * 
     */
    public function fetchTableCols($table, $schema = null)
    {
        //          name         |            type             | require | primary |                           default                           
        // ----------------------+-----------------------------+---------+---------+-------------------------------------------------------------
        //  test_autoinc_primary | integer                     | (true)  | p       | nextval('test_describe_test_autoinc_primary_seq'::regclass)
        //  test_require         | integer                     | (true)  |         | 
        //  test_bool            | boolean                     | (false) |         | 
        //  test_char            | character(7)                | (false) |         | 
        //  test_varchar         | character varying(7)        | (false) |         | 
        //  test_smallint        | smallint                    | (false) |         | 
        //  test_int             | integer                     | (false) |         | 
        //  test_bigint          | bigint                      | (false) |         | 
        //  test_numeric_size    | numeric(5,0)                | (false) |         | 
        //  test_numeric_scale   | numeric(5,3)                | (false) |         | 
        //  test_float           | double precision            | (false) |         | 
        //  test_clob            | text                        | (false) |         | 
        //  test_date            | date                        | (false) |         | 
        //  test_time            | time without time zone      | (false) |         | 
        //  test_timestamp       | timestamp without time zone | (false) |         | 
        //  test_default_null    | character(7)                | (false) |         | 
        //  test_default_string  | character(7)                | (false) |         | 'literal'::bpchar
        //  test_default_integer | integer                     | (false) |         | 7
        //  test_default_numeric | numeric(5,3)                | (false) |         | 12.345
        //  test_default_ignore  | timestamp without time zone | (false) |         | now()
        //  test_default_varchar | character varying(17)       | (false) |         | 'literal'::character varying
        //  test_default_date    | date                        | (false) |         | '1979-11-07'::date
        
        // modified from Zend_Db_Adapter_Pdo_Pgsql
        $cmd = "
            SELECT
                a.attname AS name,
                FORMAT_TYPE(a.atttypid, a.atttypmod) AS type,
                a.attnotnull AS notnull,
                co.contype AS primary,
                d.adsrc AS default
            FROM pg_attribute AS a
            JOIN pg_class AS c ON a.attrelid = c.oid
            JOIN pg_namespace AS n ON c.relnamespace = n.oid
            JOIN pg_type AS t ON a.atttypid = t.oid
            LEFT OUTER JOIN pg_constraint AS co
                ON (co.conrelid = c.oid AND a.attnum = ANY(co.conkey) AND co.contype = 'p')
            LEFT OUTER JOIN pg_attrdef AS d
                ON (d.adrelid = c.oid AND d.adnum = a.attnum)
            WHERE a.attnum > 0 AND c.relname = :table
        ";
        
        if ($schema) {
            $cmd .= " AND n.nspname = :schema";
        }
        
        $cmd .= "\n            ORDER BY a.attnum";
        
        // where the columns are stored
        $cols = array();
        
        // get the column descriptions
        $raw_cols = $this->fetchAll($cmd, array(
            'table' => $table,
            'schema' => $schema,
        ));
        
        // loop through the result rows; each describes a column.
        foreach ($raw_cols as $val) {
            $name = $val['name'];
            list($type, $size, $scale) = $this->getTypeSizeScope($val['type']);
            $cols[$name] = $this->column_factory->newInstance(
                $name,
                $type,
                ($size  ? (int) $size  : null),
                ($scale ? (int) $scale : null),
                (bool) ($val['notnull']),
                $this->getDefault($val['default']),
                (bool) (substr($val['default'], 0, 7) == 'nextval'),
                (bool) ($val['primary'])
            );
        }
        
        // done
        return $cols;
    }
    
    /**
     * 
     * Given a native column SQL default value, finds a PHP literal value.
     * 
     * SQL NULLs are converted to PHP nulls.  Non-literal values (such as
     * keywords and functions) are also returned as null.
     * 
     * @param string $default The column default SQL value.
     * 
     * @return scalar A literal PHP value.
     * 
     */
    protected function getDefault($default)
    {
        // numeric literal?
        if (is_numeric($default)) {
            return $default;
        }
        
        // string literal?
        $k = substr($default, 0, 1);
        if ($k == '"' || $k == "'") {
            // find the trailing :: typedef
            $pos = strrpos($default, '::');
            // also remove the leading and trailing quotes
            return substr($default, 1, $pos-2);
        }
        
        // null or non-literal
        return null;
    }
    
    /**
     * 
     * Returns the last ID inserted on the connection for a given table
     * and column sequence.
     * 
     * PostgreSQL uses a sequence named for the table and column to track
     * auto-incremented IDs; you need to pass the table and column name to
     * tell PostgreSQL which sequence to check.
     * 
     * @param string $table The table to check the last inserted ID on.
     * 
     * @param string $col The column to check the last inserted ID on.
     * 
     * @return mixed
     * 
     */
    public function lastInsertId($table, $col)
    {
        $name = $this->quoteName("{$table}_{$col}_seq");
        $pdo = $this->getPdo();
        return $pdo->lastInsertId($name);
    }
}
