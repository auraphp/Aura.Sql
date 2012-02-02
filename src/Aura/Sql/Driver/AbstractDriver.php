<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Driver;
use PDO;
use PDOStatement;

/**
 * 
 * Abstract class for SQL drivers.
 * 
 * @package Aura.Sql
 * 
 */
abstract class AbstractDriver
{
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
     * The PDO connection object.
     * 
     * @var PDO
     * 
     */
    protected $pdo;
    
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
        $dsn,
        $username = null,
        $password = null,
        array $options = []
    ) {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options  = array_merge($this->options, $options);
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
     * Returns the PDO connection object; if it does not exist, creates it to
     * connect to the database.
     * 
     * @return PDO
     * 
     */
    public function getPdo()
    {
        if (! $this->pdo) {
            $this->pdo = new PDO(
                $this->getDsnString(), 
                $this->username, 
                $this->password, 
                $this->options
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->pdo;
    }
    
    /**
     * 
     * Prepares and executes an SQL statement, optionally binding values
     * to named parameters in the statement.
     * 
     * This is the most-direct way to interact with the database; you pass
     * an SQL statement to the method, then the adapter uses PDO connection 
     * object to execute the statement and return a result.
     * 
     * To help prevent SQL injection attacks, you should **always** quote
     * the values used in a direct query. Use `quote()`, `quoteInto()`,
     * or  `quoteMulti()` to accomplish this.
     * 
     * Even easier, use the automated value binding provided by the `query()` 
     * method:
     * 
     *     // bad
     *     $result = $sql->query('SELECT * FROM table WHERE foo = "$bar"');
     *     
     *     // better
     *     $q_bar  = $sql->quote($bar);
     *     $result = $sql->query('SELECT * FROM table WHERE foo = $q_bar');
     *      
     *     // best
     *     $result = $sql->query(
     *         'SELECT * FROM table WHERE foo = :bar',
     *         ['bar' => $bar]
     *     );
     * 
     * The `query()` method examins the statement for all `:name` placeholders
     * and attempts to bind data from the `$data` array.  The regular
     * expression it uses is a little braindead; it cannot tell if the `:name`
     * placeholder is literal text or really a placeholder.
     * 
     * As such, you should *either* use the `$data` array for named-placeholder
     * value binding at `query()` time, *or* quote-as-you-go when building the 
     * statement, not both.  If you do, you are on your own to make sure
     * that nothing looking like a `:name` placeholder exists in the literal 
     * text.
     * 
     * Question-mark placeholders are not supported for automatic value
     * binding at query() time.
     * 
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return PDOStatement
     * 
     */
    public function query($text, array $data = [])
    {
        $stmt = $this->prepare($text, $data);
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * 
     * Creates a prepared PDOStatment and binds data values to placeholders.
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
     * @param array $data The values to bind (or quote) into the PDOStatement.
     * 
     * @return PDOStatement
     * 
     */
    public function prepare($text, array $data)
    {
        // need the PDO object regardless
        $pdo = $this->getPdo();
        
        // was data passed for binding?
        if (! $data) {
            return $pdo->prepare($text);
        }
        
        // a list of placeholders to bind at the end
        $bind = array();
        
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
                if (isset($data[$key]) && is_array($data[$key])) {
                    // quote and replace it directly, because PDO won't bind
                    // an array.
                    $find = "/(\W)(:$key)(\W)/m";
                    $repl = '${1}' . $this->quote($data[$key]) . '${3}';
                    $part = preg_replace($find, $repl, $part);
                } else {
                    // not an array, retain the placeholder name for later
                    $bind[] = $key;
                }
            }
        }
        
        // bring the parts back together in case they were modified
        $text = implode('', $parts);
        
        // prepare the statement
        $stmt = $pdo->prepare($text);
        
        // for the placeholders we found, bind the corresponding data values
        foreach ($bind as $key) {
            $stmt->bindValue($key, $data[$key]);
        }
        
        // done!
        return $stmt;
    }
    
    /**
     * 
     * Fetches all rows from the database using sequential keys.
     * 
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchAll($text, $data = [])
    {
        $stmt = $this->query($text, $data);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * Fetches all rows from the database using associative keys (defined by
     * the first column).
     * 
     * N.b.: if multiple rows have the same first column value, the last
     * row with that value will override earlier rows.
     * 
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchAssoc($text, array $data = [])
    {
        $stmt = $this->query($text, $data);
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
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchCol($text, array $data = [])
    {
        $stmt = $this->query($text, $data);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * 
     * Fetches the very first value (i.e., first column of the first row).
     * 
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return mixed
     * 
     */
    public function fetchValue($text, array $data = [])
    {
        $stmt = $this->query($text, $data);
        return $stmt->fetchColumn(0);
    }
    
    /**
     * 
     * Fetches an associative array of all rows as key-value pairs (first 
     * column is the key, second column is the value).
     * 
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchPairs($text, array $data = [])
    {
        $stmt = $this->query($text, $data);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $data[$row[0]] = $row[1];
        }
        return $data;
    }
    
    /**
     * 
     * Fetches one row from the database.
     * 
     * @param string $text The text of the SQL statement, optionally with
     * named placeholders.
     * 
     * @param array $data An associative array of data to bind to the named
     * placeholders.
     * 
     * @return array
     * 
     */
    public function fetchOne($text, array $data = [])
    {
        $stmt = $this->query($text, $data);
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
     * @param mixed $data The data value(s) to quote.
     * 
     * @return mixed An SQL-safe quoted value (or string of separated values)
     * placed into the orignal text.
     * 
     * @see quote()
     * 
     */
    public function quoteInto($text, $data)
    {
        // how many placeholders are there?
        $count = substr_count($text, '?');
        if (! $count) {
            // no replacements needed
            return $text;
        }
        
        // only one placeholder?
        if ($count == 1) {
            $data = $this->quote($data);
            $text = str_replace('?', $data, $text);
            return $text;
        }
        
        // more than one placeholder
        $offset = 0;
        foreach ((array) $data as $val) {
            
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
     * @param string $table The table to insert data into.
     * 
     * @param array $data An associative array where the key is the column
     * name and the value is the value to insert for that column.
     * 
     * @return int The number of rows affected, typically 1.
     * 
     */
    public function insert($table, $data)
    {
        // the base command text
        $table = $this->quoteName($table);
        $text = "INSERT INTO $table ";
        
        // col names come from the array keys
        $keys = array_keys($data);
        
        // quote the col names
        $cols = [];
        foreach ($keys as $key) {
            $cols[] = $this->quoteName($key);
        }
        
        // add quoted col names
        $text .= '(' . implode(', ', $cols) . ') ';
        
        // add value placeholders (use unquoted key names)
        $text .= 'VALUES (:' . implode(', :', $keys) . ')';
        
        // execute the statement
        $stmt = $this->query($text, $data);
        return $stmt->rowCount();
    }
    
    /**
     * 
     * Updates a table with specified data based on a WHERE clause.
     * 
     * @param string $table The table to udpate.
     * 
     * @param array $data An associative array where the key is the column
     * name and the value is the value to use for that column.
     * 
     * @param string|array $where The SQL WHERE clause to limit which
     * rows are updated.
     * 
     * @return int The number of rows affected.
     * 
     */
    public function update($table, $data, $where)
    {
        // the base command text
        $table = $this->quoteName($table);
        $text = "UPDATE $table SET ";
        
        // add "col = :col" pairs to the statement
        $tmp = [];
        foreach ($data as $col => $val) {
            $tmp[] = $this->quoteName($col) . " = :$col";
        }
        $text .= implode(', ', $tmp);
        
        // add the where clause
        if ($where) {
            $where = $this->quoteMulti($where, ' AND ');
            $where = $this->quoteNamesIn($where);
            $text .= " WHERE $where";
        }
        
        // execute the statement
        $stmt = $this->query($text, $data);
        return $stmt->rowCount();
    }
    
    /**
     * 
     * Deletes rows from the table based on a WHERE clause.
     * 
     * @param string $table The table to delete from.
     * 
     * @param string|array $where The SQL WHERE clause to limit which
     * rows are deleted.
     * 
     * @return int The number of rows affected.
     * 
     */
    public function delete($table, $where)
    {
        if ($where) {
            $where = $this->quoteMulti($where, ' AND ');
            $where = $this->quoteNamesIn($where);
        }
        
        $table = $this->quoteName($table);
        $stmt = $this->query("DELETE FROM $table WHERE $where");
        return $stmt->rowCount();
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
     * decimal scope.
     * 
     * @param string $spec The column specification; for example,
     * "VARCHAR(255)" or "NUMERIC(10,2)".
     * 
     * @return array A sequential array of the column type, size, and scope.
     * 
     */
    protected function getTypeSizeScope($spec)
    {
        $spec  = strtolower($spec);
        $type  = null;
        $size  = null;
        $scope = null;
        
        // find the parens, if any
        $pos = strpos($spec, '(');
        if ($pos === false) {
            // no parens, so no size or scope
            $type = $spec;
        } else {
            // find the type first.
            $type = substr($spec, 0, $pos);
            
            // there were parens, so there's at least a size.
            // remove parens to get the size.
            $size = trim(substr($spec, $pos), '()');
            
            // a comma in the size indicates a scope.
            $pos = strpos($size, ',');
            if ($pos !== false) {
                $scope = substr($size, $pos + 1);
                $size  = substr($size, 0, $pos);
            }
        }
        
        return [$type, $size, $scope];
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
     * @param string $table Return the columns in this table.
     * 
     * @param string $schema Optionally, look for the table in this schema.
     * 
     * @return array An associative array where the key is the column name
     * and the value is an array describing the column.
     * 
     */
    abstract public function fetchTableCols($table, $schema = null);
}
