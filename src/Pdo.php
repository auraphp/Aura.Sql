<?php
/**
 * 
 * This file is part of Aura for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;

use PDOStatement;

/**
 * 
 * This extended version of PDO provides:
 * 
 * - Lazy connection. The instance connects to the database only on method
 *   calls that require a connection. This means you can create an instance
 *   and not incur the cost of a connection if you never make a query.
 * 
 * - Array quoting. The quote() method will accept an array as input, and
 *   return a string of comma-separated quoted values. In addition, named
 *   placeholders in prepared statements that are bound to array values will
 *   be replaced with comma-separated quoted values. This means you can bind
 *   an array of values to a placeholder used with an `IN (...)` condition.
 * 
 * - Quoting values into placeholders.
 * 
 * - Quoting identifier names.
 * 
 * - Bind values. You may provide values for binding to the next query using
 *   bindValues(). Mulitple calls to bindValues() will merge, not reset, the
 *   values. The values will be reset after calling query(), exec(),
 *   prepare(), or any of the fetch*() methods.
 * 
 * - Fetch methods. The class provides several fetch*() methods to reduce
 *   boilerplate code elsewhere. This means you can call, e.g., fetchAll()
 *   directly on the instance instead of having to prepare a statement, bind
 *   values, execute, and then fetch from the prepared statement. All of the
 *   fetch*() methods take an array of values to bind to to the query.
 * 
 * By defult, it starts in the ERRMODE_EXCEPTION instead of ERRMODE_SILENT.
 * 
 */
class Pdo extends \PDO implements PdoInterface
{
    /**
     * 
     * A constant for the $quote_name_prefix attribute property.
     * 
     * @const string
     * 
     */
    const ATTR_QUOTE_NAME_PREFIX = 'quote_name_prefix';
    
    /**
     * 
     * A constant for the $quote_name_suffix attribute property.
     * 
     * @const string
     * 
     */
    const ATTR_QUOTE_NAME_SUFFIX = 'quote_name_suffix';
    
    /**
     * 
     * The PDO instance attributes.
     * 
     * @var array
     * 
     */
    protected $attributes = array(
        self::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        self::ATTR_EMULATE_PREPARES => true,
        self::ATTR_QUOTE_NAME_PREFIX => '"',
        self::ATTR_QUOTE_NAME_SUFFIX => '"',
    );
    
    /**
     * 
     * Values to be bound into the next query.
     * 
     * @var array
     * 
     */
    protected $bind_values = array();
    
    /**
     * 
     * Is the instance connected to a database?
     * 
     * @var bool
     * 
     */
    protected $connected = false;
    
    /**
     * 
     * The DSN for the connection.
     * 
     * @var string
     * 
     */
    protected $dsn;
    
    /**
     * 
     * The name of the driver from the DSN.
     * 
     * @var string
     * 
     */
    protected $driver;
    
    /**
     * 
     * PDO options for the connection.
     * 
     * @var array
     * 
     */
    protected $options = array();
    
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
     * The current profile information.
     * 
     * @var array
     * 
     */
    protected $profile = array();
    
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
     * The username for the connection.
     * 
     * @var string
     * 
     */
    protected $username;
    
    /**
     * 
     * Constructor; retains connection information but does not make a
     * connection.
     * 
     * @param string $dsn The data source name for the connection.
     * 
     * @param string $username The username for the connection.
     * 
     * @param string $password The password for the connection.
     * 
     * @param array $options Driver-specific options.
     * 
     * @param array $attributes Attributes to set after connection.
     * 
     * @see http://php.net/manual/en/pdo.construct.php
     * 
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = null,
        array $attributes = null
    ) {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options  = $options;
        
        // can't use array_merge, as it will renumber keys
        foreach ((array) $attributes as $attribute => $value) {
            $this->attributes[$attribute] = $value;
        }
        
        // set the driver name
        $pos = strpos($this->dsn, ':');
        $this->driver = substr($this->dsn, 0, $pos);
    }
    
    /**
     * 
     * Returns the DSN for the connection.
     * 
     * @return string
     * 
     */
    public function getDsn()
    {
        return $this->dsn;
    }
    
    /**
     * 
     * Returns the name of the driver from the DSN.
     * 
     * @return string
     * 
     */
    public function getDriver()
    {
        return $this->driver;
    }
    
    /**
     * 
     * Sets a PDO attribute value.
     * 
     * @param mixed $attribute The PDO::ATTR_* constant.
     * 
     * @param mixed $value The value for the attribute.
     * 
     * @return null
     * 
     */
    public function setAttribute($attribute, $value)
    {
        $is_attr_quote_name = $attribute == self::ATTR_QUOTE_NAME_PREFIX
                           || $attribute == self::ATTR_QUOTE_NAME_SUFFIX;
        
        if ($is_attr_quote_name) {
            return $this->setAttributeQuoteName($attribute, $value);
        }
        
        if ($this->connected) {
            return parent::setAttribute($attribute, $value);
        }
        
        $this->attributes[$attribute] = $value;
    }
    
    /**
     * 
     * Gets a PDO attribute value.
     * 
     * @param mixed $attribute The PDO::ATTR_* constant.
     * 
     * @return mixed The value for the attribute.
     * 
     */
    public function getAttribute($attribute)
    {
        if ($attribute == self::ATTR_QUOTE_NAME_PREFIX) {
            return $this->quote_name_prefix;
        }
        
        if ($attribute == self::ATTR_QUOTE_NAME_SUFFIX) {
            return $this->quote_name_suffix;
        }
        
        $this->connect();
        return parent::getAttribute($attribute);
    }
    
    /**
     * 
     * Sets the profiler object.
     * 
     * @param ProfilerInterface $profiler
     * 
     * @return null
     * 
     */
    public function setProfiler(ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
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
     * Gets the most recent error code.
     * 
     * @return mixed
     * 
     */
    public function errorCode()
    {
        $this->connect();
        return parent::errorCode();
    }
    
    /**
     * 
     * Gets the most recent error info.
     * 
     * @return array
     * 
     */
    public function errorInfo()
    {
        $this->connect();
        return parent::errorInfo();
    }
    
    /**
     * 
     * Retains several values to bind to the next query statement; these will
     * be merges with existing bound values, and will be reset after the
     * next query.
     * 
     * @param array $values An array where the key is the parameter name and
     * the value is the parameter value.
     * 
     * @return null
     * 
     */
    public function bindValues(array $bind_values)
    {
        $this->bind_values = array_merge($this->bind_values, $bind_values);
    }
    
    /**
     * 
     * Returns the array of values to bind to the next query.
     * 
     * @return array
     * 
     */
    public function getBindValues()
    {
        return $this->bind_values;
    }
    
    /**
     * 
     * Connects to the database and sets PDO attributes.
     * 
     * @return null
     * 
     * @throws PDOException if the connection fails.
     * 
     */
    public function connect()
    {
        // don't connect twice
        if ($this->connected) {
            return;
        }
        
        // connect to the database
        $this->beginProfile(__FUNCTION__);
        parent::__construct(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options
        );
        $this->endProfile();
        
        // remember that we have connected
        $this->connected = true;
        
        // set attributes
        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }
    }
    
    /**
     * 
     * Is the instance connected to a database?
     * 
     * @return bool
     * 
     */
    public function isConnected()
    {
        return $this->connected;
    }
    
    /**
     * 
     * Connects to the database and prepares an SQL statement to be executed,
     * using values that been bound for the next query.
     * 
     * This override only binds values that have placeholders in the
     * statement, thereby avoiding errors from PDO regarding too many bound
     * values.
     * 
     * If a placeholder value is an array, the array is converted to a string
     * of comma-separated quoted values; e.g., for an `IN (...)` condition.
     * The quoted string is replaced directly into the statement instead of
     * using `PDOStatement::bindValue()` proper.
     * 
     * @param string $statement The SQL statement to prepare for execution.
     * 
     * @param array $options Set these attributes on the returned
     * PDOStatement.
     * 
     * @return PDOStatement
     * 
     * @see http://php.net/manual/en/pdo.prepare.php
     * 
     */
    public function prepare($statement, $options = array())
    {
        $this->connect();
        
        // are there any bind values?
        if (! $this->bind_values) {
            return parent::prepare($statement, $options);
        }

        // a list of placeholders to bind at the end of this method
        $placeholders = array();

        // find all parts not inside quotes or backslashed-quotes
        $apos = "'";
        $quot = '"';
        $parts = preg_split(
            "/(($apos+|$quot+|\\$apos+|\\$quot+).*?)\\2/m",
            $statement,
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
                $bind_array = isset($this->bind_values[$key])
                           && is_array($this->bind_values[$key]);
                if ($bind_array) {
                    // PDO won't bind an array; quote and replace directly
                    $find = "/(\W)(:$key)(\W)/m";
                    $repl = '${1}'
                          . $this->quote($this->bind_values[$key])
                          . '${3}';
                    $part = preg_replace($find, $repl, $part);
                } else {
                    // not an array, retain the placeholder name for later
                    $placeholders[] = $key;
                }
            }
        }

        // bring the parts back together in case they were modified
        $statement = implode('', $parts);

        // prepare the statement
        $sth = parent::prepare($statement, $options);

        // for the placeholders we found, bind the corresponding data values
        foreach ($placeholders as $key) {
            $sth->bindValue($key, $this->bind_values[$key]);
        }

        // done
        return $sth;
    }
    
    /**
     * 
     * Connects to the database, prepares a statement using the bound values,
     * executes the statement, and returns the number of affected rows.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @return null
     * 
     * @see http://php.net/manual/en/pdo.exec.php
     * 
     */
    public function exec($statement)
    {
        $sth = $this->prepare($statement);
        
        $this->beginProfile(__FUNCTION__);
        $sth->execute();
        $this->endProfile($sth);
        
        $this->bind_values = array();
        return $sth->rowCount();
    }
    
    /**
     * 
     * Connects to the database, prepares a statement using the bound values,
     * executes the statement, and returns a PDOStatement result set.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param int $fetch_mode The `PDO::FETCH_*` type to set on the returned
     * `PDOStatement::setFetchMode()`.
     * 
     * @param mixed $fetch_arg1 The first additional argument to send to
     * `PDOStatement::setFetchMode()`.
     * 
     * @param mixed $fetch_arg2 The second additional argument to send to
     * `PDOStatement::setFetchMode()`.
     * 
     * @return PDOStatement
     * 
     * @see http://php.net/manual/en/pdo.query.php
     */
    public function query(
        $statement,
        $fetch_mode = null,
        $fetch_arg1 = null,
        $fetch_arg2 = null
    ) {
        // prepare and execute
        $sth = $this->prepare($statement);
        $this->beginProfile(__FUNCTION__);
        $sth->execute();
        $this->endProfile($sth);
        
        // allow for optional fetch mode
        if ($fetch_arg2 !== null) {
            $sth->setFetchMode($fetch_mode, $fetch_arg1, $fetch_arg2);
        } elseif ($fetch_arg1 !== null) {
            $sth->setFetchMode($fetch_mode, $fetch_arg1);
        } elseif ($fetch_mode !== null) {
            $sth->setFetchMode($fetch_mode);
        }
        
        // done
        $this->bind_values = array();
        return $sth;
    }
    
    /**
     * 
     * Returns the last inserted autoincrement sequence value.
     * 
     * @param string $name The name of the sequence to check; typically needed
     * only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
     * 
     * @return int
     * 
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     * 
     */
    public function lastInsertId($name = null)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = parent::lastInsertId($name);
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Fetches a sequential array of rows from the database; the rows
     * are represented as associative arrays.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @param callable $callable A callable to be applied to each of the rows
     * to be returned.
     * 
     * @return array
     * 
     */
    public function fetchAll($statement, array $values = array(), $callable = null)
    {
        $this->bindValues($values);
        $sth = $this->query($statement);
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        if ($callable) {
            foreach ($data as $key => $row) {
                $data[$key] = call_user_func($callable, $row);
            }
        }
        return $data;
    }

    /**
     * 
     * Fetches an associative array of rows from the database; the rows
     * are represented as associative arrays. The array of rows is keyed
     * on the first column of each row.
     * 
     * N.b.: if multiple rows have the same first column value, the last
     * row with that value will override earlier rows.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @param callable $callable A callable to be applied to each of the rows
     * to be returned.
     * 
     * @return array
     * 
     */
    public function fetchAssoc($statement, array $values = array(), $callable = null)
    {
        $this->bindValues($values);
        $sth = $this->query($statement);
        $data = array();
        if ($callable) {
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $key = current($row); // value of the first element
                $data[$key] = call_user_func($callable, $row);
            }
        } else {
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $key = current($row); // value of the first element
                $data[$key] = $row;
            }
        }
        return $data;
    }

    /**
     * 
     * Fetches the first column of rows as a sequential array.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @param callable $callable A callable to be applied to each of the rows
     * to be returned.
     * 
     * @return array
     * 
     */
    public function fetchCol($statement, array $values = array(), $callable = null)
    {
        $this->bindValues($values);
        $sth = $this->query($statement);
        $data = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($callable) {
            foreach ($data as $key => $val) {
                $data[$key] = call_user_func($callable, $val);
            }
        }
        return $data;
    }

    /**
     * 
     * Fetches one row from the database as an associative array.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @return array
     * 
     */
    public function fetchOne($statement, array $values = array())
    {
        $this->bindValues($values);
        $sth = $this->query($statement);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * Fetches an associative array of rows as key-value pairs (first 
     * column is the key, second column is the value).
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param callable $callable A callable to be applied to each of the rows
     * to be returned.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @return array
     * 
     */
    public function fetchPairs($statement, array $values = array(), $callable = null)
    {
        $this->bindValues($values);
        $sth = $this->query($statement);
        if ($callable) {
            $data = array();
            while ($row = $sth->fetch(PDO::FETCH_NUM)) {
                // apply the callback first so the key can be modified
                $row = call_user_func($callable, $row);
                // now retain the data
                $data[$row[0]] = $row[1];
            }
        } else {
            $data = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        return $data;
    }

    /**
     * 
     * Fetches the very first value (i.e., first column of the first row).
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @return mixed
     * 
     */
    public function fetchValue($statement, array $values = array())
    {
        $this->bindValues($values);
        $sth = $this->query($statement);
        return $sth->fetchColumn(0);
    }

    /**
     * 
     * Connects to the database, begins a transaction, and turns off
     * autocommit mode.
     * 
     * @return bool True on success, false on failure.
     * 
     * @see http://php.net/manual/en/pdo.begintransaction.php
     * 
     */
    public function beginTransaction()
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = parent::beginTransaction();
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Is a transaction currently active?
     * 
     * @return bool
     * 
     * @see http://php.net/manual/en/pdo.intransaction.php
     * 
     */
    public function inTransaction()
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = parent::inTransaction();
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Connects to the database, commits the existing transaction, and
     * restores autocommit mode.
     * 
     * @return bool True on success, false on failure.
     * 
     * @see http://php.net/manual/en/pdo.commit.php
     * 
     */
    public function commit()
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = parent::commit();
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Connects to the database, rolls back the current transaction, and
     * restores autocommit mode.
     * 
     * @return bool True on success, false on failure.
     * 
     * @see http://php.net/manual/en/pdo.rollback.php
     * 
     */
    public function rollBack()
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = parent::rollBack();
        $this->endProfile();
    }
    
    /**
     * 
     * Quotes a value for use in an SQL statement.
     * 
     * This differs from `PDO::quote()` in that it will convert an array into
     * a string of comma-separated quoted values.
     * 
     * @param mixed $value The value to quote.
     * 
     * @param int $parameter_type A data type hint for the database driver.
     * 
     * @return mixed The quoted value.
     * 
     * @see http://php.net/manual/en/pdo.quote.php
     * 
     */
    public function quote($value, $parameter_type = PDO::PARAM_STR)
    {
        $this->connect();
        
        // quote array values, not keys, then combine with commas. do not
        // recurse into sub-arrays.
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = parent::quote($v, $parameter_type);
            }
            return implode(', ', $value);
        }
        
        // normal quoting
        return parent::quote($value, $parameter_type);
    }
    
    /**
     * 
     * Given a string with question-mark placeholders, quotes the values into
     * the string, replacing the placeholders sequentially.
     * 
     * @param string $string The string with placeholders.
     * 
     * @param mixed $values The values to quote into the placeholders.
     * 
     * @return mixed An SQL-safe quoted value (or string of separated values)
     * placed into the original string.
     * 
     * @see quoteValue()
     * 
     */
    public function quoteInto($string, $values)
    {
        // how many placeholders are there?
        $count = substr_count($string, '?');
        if (! $count) {
            // no replacements needed
            return $string;
        }

        // only one placeholder?
        if ($count == 1) {
            $values = $this->quote($values);
            $string = str_replace('?', $values, $string);
            return $string;
        }

        // more than one placeholder
        $offset = 0;
        foreach ((array) $values as $val) {

            // find the next placeholder
            $pos = strpos($string, '?', $offset);
            if ($pos === false) {
                // no more placeholders, exit the data loop
                break;
            }

            // replace this question mark with a quoted value
            $val  = $this->quote($val);
            $string = substr_replace($string, $val, $pos, 1);

            // update the offset to move us past the quoted value
            $offset = $pos + strlen($val);
        }

        return $string;
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
     * @param string $name The identifier name to quote.
     * 
     * @return string|array The quoted identifier name.
     * 
     * @see replaceName()
     * 
     */
    public function quoteName($name)
    {
        // remove extraneous spaces
        $name = trim($name);

        // `original` AS `alias` ... note the 'rr' in strripos
        $pos = strripos($name, ' AS ');
        if ($pos) {
            // recurse to allow for "table.col"
            $orig  = $this->quoteName(substr($name, 0, $pos));
            // use as-is
            $alias = $this->replaceName(substr($name, $pos + 4));
            // done
            return "$orig AS $alias";
        }

        // `original` `alias`
        $pos = strrpos($name, ' ');
        if ($pos) {
            // recurse to allow for "table.col"
            $orig = $this->quoteName(substr($name, 0, $pos));
            // use as-is
            $alias = $this->replaceName(substr($name, $pos + 1));
            // done
            return "$orig $alias";
        }

        // `table`.`column`
        $pos = strrpos($name, '.');
        if ($pos) {
            // use both as-is
            $table = $this->replaceName(substr($name, 0, $pos));
            $col   = $this->replaceName(substr($name, $pos + 1));
            return "$table.$col";
        }

        // `name`
        return $this->replaceName($name);
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
     * @return string The string with names quoted in it.
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
     * Sets the value for an ATTR_QUOTE_NAME_* attribute property.
     * 
     * @param string $attribute The property to set.
     * 
     * @param string $value The value for the attribute.
     * 
     * @return null
     * 
     */
    protected function setAttributeQuoteName($attribute, $value)
    {
        $value = trim($value);
        if (! $value) {
            $message = "PDO::ATTR_QUOTE_NAME_PREFIX/SUFFIX may not be empty.";
            throw new Exception\AttrQuoteNameEmpty($message);
        }
        $this->$attribute = $value;
    }
    
    /**
     * 
     * Quotes an identifier name (table, index, etc); ignores values of '*'.
     * 
     * @param string $name The identifier name to quote.
     * 
     * @return string The quoted identifier name.
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
     * @return string The string with names quoted in it.
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
     * Begins a profile entry.
     * 
     * @param string $function The function starting the profile entry.
     * 
     * @return null
     * 
     */
    protected function beginProfile($function)
    {
        // if there's no profiler, can't profile
        if (! $this->profiler) {
            return;
        }
        
        // retain starting profile info
        $this->profile['time'] = microtime(true);
        $this->profile['function'] = $function;
        $this->profile['bind_values'] = $this->bind_values;
    }
    
    /**
     * 
     * Ends and records a profile entry.
     * 
     * @param PDOStatement $sth The statement being profiled, if any.
     * 
     * @return null
     * 
     */
    protected function endProfile(PDOStatement $sth = null)
    {
        // if there's no profiler, can't profile
        if (! $this->profiler) {
            return;
        }
        
        // add an entry to the profiler
        $this->profiler->addProfile(
            microtime(true) - $this->profile['time'],
            $this->profile['function'],
            $sth ? $sth->queryString : null,
            $this->profile['bind_values']
        );
        
        // clear the starting profile info
        $this->profile = array();
    }
}
