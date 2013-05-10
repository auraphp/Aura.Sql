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

use Aura\Sql\ColumnFactory;
use Aura\Sql\ProfilerInterface;
use Aura\Sql\Query\AbstractQuery;
use Aura\Sql\Query\Factory as QueryFactory;
use PDO;
use PDOStatement;

/**
 * 
 * Abstract class for SQL drivers.
 * 
 * @package Aura.Sql
 * 
 */
abstract class AbstractConnection
{
    /**
     * 
     * A ColumnFactory for returning column information.
     * 
     * @var ColumnFactory
     * 
     */
    protected $column_factory;

    /**
     * 
     * The PDO DSN for the connection. This can be an array of key-value pairs
     * or a string (minus the PDO type prefix).
     * 
     * @var string|array
     * 
     */
    protected $dsn;

    /**
     * 
     * The PDO type prefix.
     * 
     * @var string
     * 
     */
    protected $dsn_prefix;

    /**
     * 
     * PDO options for the connection.
     * 
     * @var array
     * 
     */
    protected $options = [];

    /**
     * 
     * The password for the connection.
     * 
     * @var string
     * 
     */
    protected $password;

    /**
     * 
     * A query profiler.
     * 
     * @var ProfilerInterface
     * 
     */
    protected $profiler;

    /**
     * 
     * The PDO connection object.
     * 
     * @var PDO
     * 
     */
    protected $pdo;

    /**
     * 
     * A query factory
     * 
     * @var QueryFactory
     * 
     */
    protected $query_factory;

    /**
     * 
     * The prefix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_prefix;

    /**
     * 
     * The suffix to use when quoting identifier names.
     * 
     * @var string
     * 
     */
    protected $quote_name_suffix;

    /**
     * 
     * The username for the connection.
     * 
     * @var string
     * 
     */
    protected $username;

    /**
     * 
     * Constructor.
     * 
     * @param ProfilerInterface $profiler A query profiler.
     * 
     * @param ColumnFactory $column_factory A column object factory.
     * 
     * @param QueryFactory $query_factory A query object factory.
     * 
     * @param mixed $dsn DSN parameters for the PDO connection.
     * 
     * @param string $username The username for the PDO connection.
     * 
     * @param string $password The password for the PDO connection.
     * 
     * @param array $options Options for PDO connection.
     * 
     */
    public function __construct(
        ProfilerInterface $profiler,
        ColumnFactory $column_factory,
        QueryFactory $query_factory,
        $dsn = null,
        $username = null,
        $password = null,
        array $options = []
    ) {
        $this->profiler       = $profiler;
        $this->column_factory = $column_factory;
        $this->query_factory  = $query_factory;
        $this->dsn            = $dsn;
        $this->username       = $username;
        $this->password       = $password;
        $this->options        = array_merge($this->options, $options);
    }

    /**
     * 
     * Returns the profiler object.
     * 
     * @return ProfilerInterface
     * 
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * 
     * Returns the column factory object.
     * 
     * @return ColumnFactory
     * 
     */
    public function getColumnFactory()
    {
        return $this->column_factory;
    }

    /**
     * 
     * Returns the query factory object.
     * 
     * @return QueryFactory
     * 
     */
    public function getQueryFactory()
    {
        return $this->query_factory;
    }

    /**
     * 
     * Returns the DSN string used by the PDO connection.
     * 
     * @return string
     * 
     */
    public function getDsnString()
    {
        if (is_array($this->dsn)) {
            $dsn_string = '';
            foreach ($this->dsn as $key => $val) {
                if ($val !== null) {
                    $dsn_string .= "$key=$val;";
                }
            }
            $dsn_string = rtrim($dsn_string, ';');
        } else {
            $dsn_string = $this->dsn;
        }

        return "{$this->dsn_prefix}:{$dsn_string}";
    }

    /**
     * 
     * Sets the PDO connection object; typically used when a shared PDO object
     * already exists in a legacy context.
     * 
     * Note that if you use setPdo(), the pre- and post-connect method hooks
     * will not be called.
     * 
     * @param PDO $pdo The PDO object.
     * 
     * @return void
     * 
     */
    public function setPdo(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
    }

    /**
     * 
     * Returns the PDO connection object; if it does not exist, creates it to
     * connect to the database.
     * 
     * @return PDO
     * 
     */
    public function getPdo()
    {
        $this->connect();
        return $this->pdo;
    }

    /**
     * 
     * Connects to the database by creating the PDO object.
     * 
     * @return void
     * 
     */
    public function connect()
    {
        if ($this->pdo) {
            return;
        }

        $this->preConnect();
        $this->pdo = $this->newPdo();
        $this->postConnect();
    }

    /**
     * 
     * A hook that executes before the database connection is created.
     * 
     * @return void
     * 
     */
    public function preConnect()
    {
    }

    /**
     * 
     * A hook that executes after the database connection is created.
     * 
     * @return void
     * 
     */
    public function postConnect()
    {
    }

    /**
     * 
     * Prepares and executes an SQL query, optionally binding values
     * to named placeholders in the query text.
     * 
     * @param string|AbstractQuery $query The text of the SQL query; or, a
     * query object.
     * 
     * @param array $bind An associative array of data to bind to named
     * placeholders in the query.
     * 
     * @return PDOStatement
     * 
     */
    public function query($query, array $bind = [])
    {
        // casts objects to string via __toString()
        $stmt = $this->prepare((string) $query, $bind);
        $this->profiler->exec($stmt, $bind);
        return $stmt;
    }

    /**
     * 
     * Begins a database transaction and turns off autocommit.
     * 
     * @return mixed
     * 
     */
    public function beginTransaction()
    {
        $pdo = $this->getPdo();
        $func = function() use ($pdo) {
            return $pdo->beginTransaction();
        };
        return $this->profiler->call($func, '__BEGIN_TRANSACTION__');
    }

    /**
     * 
     * Commits the current database transaction and turns autocommit back on.
     * 
     * @return mixed
     * 
     */
    public function commit()
    {
        $pdo = $this->getPdo();
        $func = function() use ($pdo) {
            return $pdo->commit();
        };
        return $this->profiler->call($func, '__COMMIT__');
    }

    /**
     * 
     * Rolls back the current database transaction and turns autocommit back
     * on.
     * 
     * @return mixed
     * 
     */
    public function rollback()
    {
        $pdo = $this->getPdo();
        $func = function() use ($pdo) {
            return $pdo->rollBack();
        };
        return $this->profiler->call($func, '__ROLLBACK__');
    }

    /**
     * 
     * Creates a prepared PDOStatement and binds data values to placeholders.
     * 
     * PDO itself is touchy about binding values.  If you attempt to bind a
     * value that does not have a corresponding placeholder, PDO will error.
     * This method checks the query text to find placeholders and binds only
     * data values that have placeholders in the text.
     * 
     * Similarly, PDO won't bind an array value. This method checks to see if
     * the data to be bound is an array; if it is, the array is quoted and
     * replaced into the text directly instead of binding it.
     * 
     * @param string $text The text of the SQL query.
     * 
     * @param array $bind The values to bind (or quote) into the PDOStatement.
     * 
     * @return PDOStatement
     * 
     */
    public function prepare($text, array $bind)
    {
        // need the PDO object regardless
        $pdo = $this->getPdo();

        // was data passed for binding?
        if (! $bind) {
            return $pdo->prepare($text);
        }

        // a list of placeholders to bind at the end
        $placeholders = array();

        // find all text parts not inside quotes or backslashed-quotes
        $apos = "'";
        $quot = '"';
        $parts = preg_split(
            "/(($apos+|$quot+|\\$apos+|\\$quot+).*?)\\2/m",
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // loop through the non-quoted parts (0, 3, 6, 9, etc.)
        $k = count($parts);
        for ($i = 0; $i <= $k; $i += 3) {

            // get the part as a reference so it can be modified in place
            $part =& $parts[$i];

            // find all :placeholder matches in the part
            preg_match_all(
                "/\W:([a-zA-Z_][a-zA-Z0-9_]*)/m",
                $part . PHP_EOL,
                $matches
            );

            // for each of the :placeholder matches ...
            foreach ($matches[1] as $key) {
                // is the corresponding data element an array?
                if (isset($bind[$key]) && is_array($bind[$key])) {
                    // quote and replace it directly, because PDO won't bind
                    // an array.
                    $find = "/(\W)(:$key)(\W)/m";
                    $repl = '${1}' . $this->quote($bind[$key]) . '${3}';
                    $part = preg_replace($find, $repl, $part);
                } else {
                    // not an array, retain the placeholder name for later
                    $placeholders[] = $key;
                }
            }
        }

        // bring the parts back together in case they were modified
        $text = implode('', $parts);

        // prepare the statement
        $stmt = $pdo->prepare($text);

        // for the placeholders we found, bind the corresponding data values
        foreach ($placeholders as $key) {
            $stmt->bindValue($key, $bind[$key]);
        }

        // done!
        return $stmt;
    }

    /**
     * 
     * Fetches a sequential array of all rows from the database; the rows
     * are represented as associative arrays.
     * 
     * @param string|AbstractQuery $query The text of the SQL query; or, a
     * query object.
     * 
     * @param array $bind An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchAll($query, $bind = [])
    {
        $stmt = $this->query($query, $bind);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 
     * Fetches an associative array of all rows from the database; the rows
     * are represented as associative arrays. The array of all rows is keyed
     * on the first column of each row.
     * 
     * N.b.: if multiple rows have the same first column value, the last
     * row with that value will override earlier rows.
     * 
     * @param string $query The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchAssoc($query, array $data = [])
    {
        $stmt = $this->query($query, $data);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = current($row); // value of the first element
            $data[$key] = $row;
        }
        return $data;
    }

    /**
     * 
     * Fetches the first column of all rows as a sequential array.
     * 
     * @param string|AbstractQuery $query The text of the SQL query; or, a
     * query object.
     * 
     * @param array $bind An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchCol($query, array $bind = [])
    {
        $stmt = $this->query($query, $bind);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * 
     * Fetches the very first value (i.e., first column of the first row).
     * 
     * @param string|AbstractQuery $query The text of the SQL query; or, a
     * query object.
     * 
     * @param array $bind An associative array of data to bind to the named
     * placeholders.
     * 
     * @return mixed
     * 
     */
    public function fetchValue($query, array $bind = [])
    {
        $stmt = $this->query($query, $bind);
        return $stmt->fetchColumn(0);
    }

    /**
     * 
     * Fetches an associative array of all rows as key-value pairs (first 
     * column is the key, second column is the value).
     * 
     * @param string|AbstractQuery $query The text of the SQL query; or, a
     * query object.
     * 
     * @param array $bind An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchPairs($query, array $bind = [])
    {
        $stmt = $this->query($query, $bind);
        $bind = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $bind[$row[0]] = $row[1];
        }
        return $bind;
    }

    /**
     * 
     * Fetches one row from the database as an associative array.
     * 
     * @param string|AbstractQuery $query The text of the SQL query; or, a
     * query object.
     * 
     * @param array $bind An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchOne($query, array $bind = [])
    {
        $stmt = $this->query($query, $bind);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 
     * Safely quotes a value for an SQL statement.
     * 
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string; this is useful 
     * for generating `IN()` lists.
     * 
     * @param mixed $val The value to quote.
     * 
     * @return string An SQL-safe quoted value (or a string of 
     * separated-and-quoted values).
     * 
     */
    public function quote($val)
    {
        if (is_array($val)) {
            // quote array values, not keys, then combine with commas.
            foreach ($val as $k => $v) {
                $val[$k] = $this->quote($v);
            }
            return implode(', ', $val);
        } elseif (is_numeric($val)) {
            return $val;
        } else {
            // quote all other scalars, including numerics
            $pdo = $this->getPdo();
            return $pdo->quote($val);
        }
    }

    /**
     * 
     * Quotes a value and places into a piece of text at a placeholder; the
     * placeholder is a question-mark.
     * 
     * @param string $text The text with placeholder(s).
     * 
     * @param mixed $bind The data value(s) to quote.
     * 
     * @return mixed An SQL-safe quoted value (or string of separated values)
     * placed into the original text.
     * 
     * @see quote()
     * 
     */
    public function quoteInto($text, $bind)
    {
        // how many placeholders are there?
        $count = substr_count($text, '?');
        if (! $count) {
            // no replacements needed
            return $text;
        }

        // only one placeholder?
        if ($count == 1) {
            $bind = $this->quote($bind);
            $text = str_replace('?', $bind, $text);
            return $text;
        }

        // more than one placeholder
        $offset = 0;
        foreach ((array) $bind as $val) {

            // find the next placeholder
            $pos = strpos($text, '?', $offset);
            if ($pos === false) {
                // no more placeholders, exit the data loop
                break;
            }

            // replace this question mark with a quoted value
            $val  = $this->quote($val);
            $text = substr_replace($text, $val, $pos, 1);

            // update the offset to move us past the quoted value
            $offset = $pos + strlen($val);
        }

        return $text;
    }

    /**
     * 
     * Quote multiple text-and-value pieces.
     * 
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example ...
     * 
     *     $list = [
     *          "WHERE date > ?"   => '2005-01-01',
     *          "  AND date < ?"   => '2005-02-01',
     *          "  AND type IN(?)" => ['a', 'b', 'c'],
     *     ];
     *     
     *     $safe = $sql->quoteMulti($list);
     *     
     *     // $safe = "WHERE date > '2005-01-02'
     *     //          AND date < 2005-02-01
     *     //          AND type IN('a','b','c')"
     * }}
     * 
     * @param array $list A series of key-value pairs where the key is
     * the placeholder text and the value is the value to be quoted into
     * it.  If the key is an integer, it is assumed that the value is
     * piece of literal text to be used and not quoted.
     * 
     * @param string $sep Return the list pieces separated with this string
     * (for example ' AND ').
     * 
     * @return string An SQL-safe string composed of the list keys and
     * quoted values.
     * 
     */
    public function quoteMulti($list, $sep)
    {
        $text = [];
        foreach ((array) $list as $key => $val) {
            if (is_int($key)) {
                // integer $key means a literal phrase and no value to
                // be bound into it
                $text[] = $val;
            } else {
                // string $key means a phrase with a placeholder, and
                // $val should be bound into it.
                $text[] = $this->quoteInto($key, $val);
            }
        }

        // return the condition list
        return implode($sep, $text);
    }

    /**
     * 
     * Quotes a single identifier name (table, table alias, table column, 
     * index, sequence).
     * 
     * If the name contains `' AS '`, this method will separately quote the
     * parts before and after the `' AS '`.
     * 
     * If the name contains a space, this method will separately quote the
     * parts before and after the space.
     * 
     * If the name contains a dot, this method will separately quote the
     * parts before and after the dot.
     * 
     * @param string $spec The identifier name to quote.
     * 
     * @return string|array The quoted identifier name.
     * 
     * @see replaceName()
     * 
     */
    public function quoteName($spec)
    {
        // remove extraneous spaces
        $spec = trim($spec);

        // `original` AS `alias` ... note the 'rr' in strripos
        $pos = strripos($spec, ' AS ');
        if ($pos) {
            // recurse to allow for "table.col"
            $orig  = $this->quoteName(substr($spec, 0, $pos));
            // use as-is
            $alias = $this->replaceName(substr($spec, $pos + 4));
            // done
            return "$orig AS $alias";
        }

        // `original` `alias`
        $pos = strrpos($spec, ' ');
        if ($pos) {
            // recurse to allow for "table.col"
            $orig = $this->quoteName(substr($spec, 0, $pos));
            // use as-is
            $alias = $this->replaceName(substr($spec, $pos + 1));
            // done
            return "$orig $alias";
        }

        // `table`.`column`
        $pos = strrpos($spec, '.');
        if ($pos) {
            // use both as-is
            $table = $this->replaceName(substr($spec, 0, $pos));
            $col   = $this->replaceName(substr($spec, $pos + 1));
            return "$table.$col";
        }

        // `name`
        return $this->replaceName($spec);
    }

    /**
     * 
     * Quotes all fully-qualified identifier names ("table.col") in a string,
     * typically an SQL snippet for a SELECT clause.
     * 
     * Does not quote identifier names that are string literals (i.e., inside
     * single or double quotes).
     * 
     * Looks for a trailing ' AS alias' and quotes the alias as well.
     * 
     * @param string $text The string in which to quote fully-qualified
     * identifier names to quote.
     * 
     * @return string|array The string with names quoted in it.
     * 
     * @see replaceNamesIn()
     * 
     */
    public function quoteNamesIn($text)
    {
        // single and double quotes
        $apos = "'";
        $quot = '"';

        // look for ', ", \', or \" in the string.
        // match closing quotes against the same number of opening quotes.
        $list = preg_split(
            "/(($apos+|$quot+|\\$apos+|\\$quot+).*?\\2)/",
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // concat the pieces back together, quoting names as we go.
        $text = null;
        $last = count($list) - 1;
        foreach ($list as $key => $val) {

            // skip elements 2, 5, 8, 11, etc. as artifacts of the back-
            // referenced split; these are the trailing/ending quote
            // portions, and already included in the previous element.
            // this is the same as every third element from zero.
            if (($key+1) % 3 == 0) {
                continue;
            }

            // is there an apos or quot anywhere in the part?
            $is_string = strpos($val, $apos) !== false ||
                         strpos($val, $quot) !== false;

            if ($is_string) {
                // string literal
                $text .= $val;
            } else {
                // sql language.
                // look for an AS alias if this is the last element.
                if ($key == $last) {
                    // note the 'rr' in strripos
                    $pos = strripos($val, ' AS ');
                    if ($pos) {
                        // quote the alias name directly
                        $alias = $this->replaceName(substr($val, $pos + 4));
                        $val = substr($val, 0, $pos) . " AS $alias";
                    }
                }

                // now quote names in the language.
                $text .= $this->replaceNamesIn($val);
            }
        }

        // done!
        return $text;
    }

    /**
     * 
     * Inserts a row of data into a table.
     * 
     * @param string $table The table to insert into.
     * 
     * @param array $cols An associative array where the key is the column
     * name and the value is the value to insert for that column.
     * 
     * @return int The number of rows affected, typically 1.
     * 
     */
    public function insert($table, array $cols)
    {
        $insert = $this->newInsert();
        $insert->into($table);
        $insert->cols(array_keys($cols));
        $stmt = $this->query($insert, $cols);
        return $stmt->rowCount();
    }

    /**
     * 
     * Updates a table with specified data based on WHERE conditions.
     * 
     * @param string $table The table to update.
     * 
     * @param array $cols An associative array where the key is the column
     * name and the value is the value to use for that column.
     * 
     * @param string $cond Conditions for a WHERE clause.
     * 
     * @param array $bind Additional data to bind to the query; these are not
     * part of the update, and note that the $cols values will take precedence
     * over these additional values.
     * 
     * @return int The number of rows affected.
     * 
     */
    public function update($table, array $cols, $cond, array $bind = [])
    {
        $update = $this->newUpdate();
        $update->table($table);
        $update->cols(array_keys($cols));
        if ($cond) {
            $update->where($cond);
        }
        $bind = array_merge($bind, $cols);
        $stmt = $this->query($update, $bind);
        return $stmt->rowCount();
    }

    /**
     * 
     * Deletes rows from the table based on WHERE conditions.
     * 
     * @param string $table The table to delete from.
     * 
     * @param string $cond Conditions for a WHERE clause.
     * 
     * @param array $bind Additional data to bind to the query.
     * 
     * @return int The number of rows affected.
     * 
     */
    public function delete($table, $cond, array $bind = [])
    {
        $delete = $this->newDelete();
        $delete->from($table);
        if ($cond) {
            $delete->where($cond);
        }
        $stmt = $this->query($delete, $bind);
        return $stmt->rowCount();
    }

    /**
     * 
     * Returns a new Select object.
     * 
     * @return Select
     * 
     */
    public function newSelect()
    {
        return $this->query_factory->newInstance('select', $this);
    }

    /**
     * 
     * Returns a new Insert object.
     * 
     * @return Insert
     * 
     */
    public function newInsert()
    {
        return $this->query_factory->newInstance('insert', $this);
    }

    /**
     * 
     * Returns a new Update object.
     * 
     * @return Update
     * 
     */
    public function newUpdate()
    {
        return $this->query_factory->newInstance('update', $this);
    }

    /**
     * 
     * Returns a new Delete object.
     * 
     * @return Update
     * 
     */
    public function newDelete()
    {
        return $this->query_factory->newInstance('delete', $this);
    }

    /**
     * 
     * Modifies an SQL string **in place** to add a `LIMIT ... OFFSET` clause.
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

        if ($count && $offset) {
            $text .= "LIMIT $count OFFSET $offset" . PHP_EOL;
        } elseif ($count) {
            $text .= "LIMIT $count" . PHP_EOL;
        } elseif ($offset) {
            $text .= "OFFSET $offset" . PHP_EOL;
        }
    }

    /**
     * 
     * Creates a new PDO object.
     * 
     * @return PDO
     * 
     */
    protected function newPdo()
    {
        $pdo = new PDO(
            $this->getDsnString(),
            $this->username,
            $this->password,
            $this->options
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    /**
     * 
     * Quotes an identifier name (table, index, etc); ignores empty values and
     * values of '*'.
     * 
     * @param string $name The identifier name to quote.
     * 
     * @return string The quoted identifier name.
     * 
     * @see quoteName()
     * 
     */
    protected function replaceName($name)
    {
        $name = trim($name);
        if ($name == '*') {
            return $name;
        } else {
            return $this->quote_name_prefix
                 . $name
                 . $this->quote_name_suffix;
        }
    }

    /**
     * 
     * Quotes all fully-qualified identifier names ("table.col") in a string.
     * 
     * @param string $text The string in which to quote fully-qualified
     * identifier names to quote.
     * 
     * @return string|array The string with names quoted in it.
     * 
     * @see quoteNamesIn()
     * 
     */
    protected function replaceNamesIn($text)
    {
        $word = "[a-z_][a-z0-9_]+";

        $find = "/(\\b)($word)\\.($word)(\\b)/i";

        $repl = '$1'
              . $this->quote_name_prefix
              . '$2'
              . $this->quote_name_suffix
              . '.'
              . $this->quote_name_prefix
              . '$3'
              . $this->quote_name_suffix
              . '$4'
              ;

        $text = preg_replace($find, $repl, $text);

        return $text;
    }

    /**
     * 
     * Given a column specification, parse into datatype, size, and 
     * decimal scale.
     * 
     * @param string $spec The column specification; for example,
     * "VARCHAR(255)" or "NUMERIC(10,2)".
     * 
     * @return array A sequential array of the column type, size, and scale.
     * 
     */
    protected function getTypeSizeScope($spec)
    {
        $spec  = strtolower($spec);
        $type  = null;
        $size  = null;
        $scale = null;

        // find the parens, if any
        $pos = strpos($spec, '(');
        if ($pos === false) {
            // no parens, so no size or scale
            $type = $spec;
        } else {
            // find the type first.
            $type = substr($spec, 0, $pos);

            // there were parens, so there's at least a size.
            // remove parens to get the size.
            $size = trim(substr($spec, $pos), '()');

            // a comma in the size indicates a scale.
            $pos = strpos($size, ',');
            if ($pos !== false) {
                $scale = substr($size, $pos + 1);
                $size  = substr($size, 0, $pos);
            }
        }

        return [$type, $size, $scale];
    }

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
    abstract public function fetchTableList($schema = null);

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
    abstract public function fetchTableCols($spec);

    /**
     * 
     * Splits an identifier name into two parts, based on the location of the
     * first dot.
     * 
     * @param string $name The identifier name to be split.
     * 
     * @return array An array of two elements; element 0 is the parts before
     * the dot, and element 1 is the part after the dot. If there was no dot,
     * element 0 will be null and element 1 will be the name as given.
     * 
     */
    protected function splitName($name)
    {
        $pos = strpos($name, '.');
        if ($pos === false) {
            return [null, $name];
        } else {
            return [substr($name, 0, $pos), substr($name, $pos+1)];
        }
    }
}
