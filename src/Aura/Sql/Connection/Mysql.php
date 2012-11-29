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
 * MySQL connection adapter.
 * 
 * @package Aura.Sql
 * 
 */
class Mysql extends AbstractConnection
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
        'unix_socket' => null,
        'charset' => null,
    ];

    /**
     * 
     * The PDO type prefix.
     * 
     * @var string
     * 
     */
    protected $dsn_prefix = 'mysql';

    /**
     * 
     * The prefix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_prefix = '`';

    /**
     * 
     * The suffix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_suffix = '`';

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
        $text = 'SHOW TABLES';
        if ($schema) {
            $text .= ' IN ' . $this->replaceName($schema);
        }
        return $this->fetchCol($text);
    }

    /**
     * 
     * Returns an array of columns in a table.
     * 
     * @param string $spec Return the columns in this table. This may be just
     * a `table` name, or a `schema.table` name.
     * 
     * @return array An associative array where the key is the column name
     * and the value is a Column object.
     * 
     */
    public function fetchTableCols($spec)
    {
        list($schema, $table) = $this->splitName($spec);

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
            list($type, $size, $scale) = $this->getTypeSizeScope($val['Type']);

            // save the column description
            $cols[$name] = $this->column_factory->newInstance(
                $name,
                $type,
                ($size  ? (int) $size  : null),
                ($scale ? (int) $scale : null),
                (bool) ($val['Null'] != 'YES'),
                $this->getDefault($val['Default']),
                (bool) (strpos($val['Extra'], 'auto_increment') !== false),
                (bool) ($val['Key'] == 'PRI')
            );
        }

        // done!
        return $cols;
    }

    /**
     * 
     * A helper method to get the default value for a column.
     * 
     * @param string $default The default value as reported by MySQL.
     * 
     * @return string
     * 
     */
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
