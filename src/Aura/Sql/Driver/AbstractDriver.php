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
 * Abstract Class for Driver
 * 
 * @package Aura.Sql
 * 
 */
abstract class AbstractDriver
{
    protected $dsn_prefix;
    
    protected $dsn;
    
    protected $ident_quote_prefix = null;
    
    protected $ident_quote_suffix = null;
    
    protected $username;
    
    protected $password;
    
    protected $options = [];
    
    protected $pdo;
    
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
    
    public function query($text, array $data = [])
    {
        $pdo = $this->getPdo();
        $stmt = $pdo->prepare($text);
        $this->bind($stmt, $data);
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * 
     * Fetches all rows from the database using sequential keys.
     * 
     * @param string $spec The SELECT statement.
     * 
     * @param array $data An associative array of data to bind into the
     * SELECT statement.
     * 
     * @return array
     * 
     */
    public function fetchAll($spec, $data = [])
    {
        $stmt = $this->query($spec, $data);
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
     * @param array|string $spec An array of component parts for a
     * SELECT, or a literal query string.
     * 
     * @param array $data An associative array of data to bind into the
     * SELECT statement.
     * 
     * @return array
     * 
     */
    public function fetchAssoc($spec, array $data = [])
    {
        $stmt = $this->query($spec, $data);
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
     * @param array|string $spec An array of component parts for a
     * SELECT, or a literal query string.
     * 
     * @param array $data An associative array of data to bind into the
     * SELECT statement.
     * 
     * @return array
     * 
     */
    public function fetchCol($spec, array $data = [])
    {
        $stmt = $this->query($spec, $data);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * 
     * Fetches the very first value (i.e., first column of the first row).
     * 
     * @param array|string $spec An array of component parts for a
     * SELECT, or a literal query string.
     * 
     * @param array $data An associative array of data to bind into the
     * SELECT statement.
     * 
     * @return mixed
     * 
     */
    public function fetchValue($spec, array $data = [])
    {
        $stmt = $this->query($spec, $data);
        return $stmt->fetchColumn(0);
    }
    
    /**
     * 
     * Fetches an associative array of all rows as key-value pairs (first 
     * column is the key, second column is the value).
     * 
     * @param array|string $spec An array of component parts for a
     * SELECT, or a literal query string.
     * 
     * @param array $data An associative array of data to bind into the
     * SELECT statement.
     * 
     * @return array
     * 
     */
    public function fetchPairs($spec, array $data = [])
    {
        $stmt = $this->query($spec, $data);
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
     * @param array|string $spec An array of component parts for a
     * SELECT, or a literal query string.
     * 
     * @param array $data An associative array of data to bind into the
     * SELECT statement.
     * 
     * @return array
     * 
     */
    public function fetchOne($spec, array $data = [])
    {
        $stmt = $this->query($spec, $data);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * Safely quotes a value for an SQL statement.
     * 
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string; this is useful 
     * for generating IN() lists.
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
     * {{code: php
     *     $sql = Solar::factory('Solar_Sql');
     *     
     *     $list = [
     *          "WHERE date > ?"   => '2005-01-01',
     *          "  AND date < ?"   => '2005-02-01',
     *          "  AND type IN(?)" => ['a', 'b', 'c'],
     *     ];
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
     * index, sequence).  Ignores empty values.
     * 
     * If the name contains ' AS ', this method will separately quote the
     * parts before and after the ' AS '.
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
        // no extraneous spaces
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
     * Binds an array of scalars as values into a prepared PDOStatment.
     * 
     * Array element values that are themselves arrays will not be bound
     * correctly, because PDO expects scalar values only.
     * 
     * @param PDOStatement $stmt The prepared PDOStatement.
     * 
     * @param array $data The scalar values to bind into the PDOStatement.
     * 
     * @return void
     * 
     */
    protected function bind(PDOStatement $stmt, array $data)
    {
        // was data passed for binding?
        if (! $data) {
            return;
        }
            
        // find all :placeholder matches.  note that this is a little
        // brain-dead; it will find placeholders in literal text, which
        // will cause errors later.  so in general, you should *either*
        // bind at query time *or* bind as you go, not both.
        preg_match_all(
            "/\W:([a-zA-Z_][a-zA-Z0-9_]*)/m",
            $stmt->queryString . "\n",
            $matches
        );
        
        // bind values to placeholders
        foreach ($matches[1] as $key) {
            $stmt->bindValue($key, $data[$key]);
        }
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
            return $this->ident_quote_prefix
                 . $name
                 . $this->ident_quote_suffix;
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
              . $this->ident_quote_prefix
              . '$2'
              . $this->ident_quote_suffix
              . '.'
              . $this->ident_quote_prefix
              . '$3'
              . $this->ident_quote_suffix
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
     * Returns a list of database tables.
     * 
     * @return array The list of tables in the database.
     * 
     */
    abstract public function fetchTableList($schema = null);
    
    abstract public function fetchTableCols($table, $schema = null);
}
