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
 * Microsoft SQL Server connection adapter.
 * 
 * @package Aura.Sql
 * 
 */
class Sqlsrv extends AbstractConnection
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
        'Server' => null,
        'Database' => null,
    ];

    /**
     * 
     * The PDO type prefix.
     * 
     * @var string
     * 
     */
    protected $dsn_prefix = 'sqlsrv';

    /**
     * 
     * The prefix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_prefix = '[';

    /**
     * 
     * The suffix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_suffix = ']';

    /**
     * 
     * Returns a list of all tables in the database.
     * 
     * @param string $schema Fetch tbe list of tables in this schema; 
     * when empty, uses the default schema.
     * 
     * @return array All table names in the database.
     * 
     * @todo Honor the $schema param.
     * 
     */
    public function fetchTableList($schema = null)
    {
        $text = "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
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
     * @todo Honor `schema.table` as the specification.
     * 
     */
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

    /**
     * 
     * Modifies an SQL string **in place** to add a `TOP` or 
     * `FETCH NEXT ... OFFSET` clause.
     * 
     * @param string $text The SQL string.
     * 
     * @param int $count The number of rows to return.
     * 
     * @param int $offset Skip this many rows first.
     * 
     * @return void
     * 
     */
    public function limit(&$text, $count, $offset = 0)
    {
        $count  = (int) $count;
        $offset = (int) $offset;

        if ($count && ! $offset) {
            // count, but no offset, so we can use TOP
            $text = preg_replace('/^(SELECT( DISTINCT)?)/', "$1 TOP $count", $text);
        } elseif ($count && $offset) {
            // count and offset, use FETCH NEXT
            $text .= "OFFSET $offset ROWS" . PHP_EOL
                   . "FETCH NEXT $count ROWS ONLY" . PHP_EOL;
        }
    }
}
