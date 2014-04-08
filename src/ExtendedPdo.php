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

use PDO;
use PDOStatement;

/**
 * 
 * This extended decorator for PDO provides lazy connection, array quoting, a
 * new `perform()` method, and new `fetch*()` methods.
 * 
 */
class ExtendedPdo extends PDO implements ExtendedPdoInterface
{
    /**
     *
     * The instance of PDO being decorated.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     * 
     * The attributes for a lazy connection.
     * 
     * @var array
     * 
     */
    protected $attributes = array(
        self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION,
        self::ATTR_EMULATE_PREPARES => true,
    );
    
    /**
     * 
     * The DSN for a lazy connection.
     * 
     * @var string
     * 
     */
    protected $dsn;
    
    /**
     * 
     * PDO options for a lazy connection.
     * 
     * @var array
     * 
     */
    protected $options = array();
    
    /**
     * 
     * The password for a lazy connection.
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
     * The username for a lazy connection.
     * 
     * @var string
     * 
     */
    protected $username;
    
    /**
     * 
     * This constructor is pseudo-polymorphic. You may pass a normal set of PDO
     * constructor parameters, and ExtendedPdo will use them for a lazy
     * connection. Alternatively, if the `$dsn` parameter is an existing PDO 
     * instance, that instance will be decorated by ExtendedPdo; the remaining
     * parameters will be ignored.
     * 
     * @param PDO|string $dsn The data source name for a lazy PDO connection, 
     * or an existing instance of PDO. If the latter, the remaining params are
     * ignored.
     * 
     * @param string $username The username for a lazy connection.
     * 
     * @param string $password The password for a lazy connection.
     * 
     * @param array $options Driver-specific options for a lazy connection.
     * 
     * @param array $attributes Attributes to set after a lazy connection.
     * 
     * @see http://php.net/manual/en/pdo.construct.php
     * 
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = array(),
        array $attributes = array()
    ) {
        if ($dsn instanceof PDO) {
            $this->pdo = $dsn;
        } else {
            $this->dsn = $dsn;
            $this->username = $username;
            $this->password = $password;
            $this->options = $options;
            $this->attributes = array_replace($this->attributes, $attributes);
        }
    }
    
    /**
     * 
     * Begins a transaction and turns off autocommit mode.
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
        $result = $this->pdo->beginTransaction();
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Commits the existing transaction and restores autocommit mode.
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
        $result = $this->pdo->commit();
        $this->endProfile();
        return $result;
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
        if ($this->pdo) {
            return;
        }
        
        // connect to the database
        $this->beginProfile(__FUNCTION__);
        $this->pdo = new PDO(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options
        );
        $this->endProfile();
        
        // set attributes
        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }
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
        return $this->pdo->errorCode();
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
        return $this->pdo->errorInfo();
    }
    
    /**
     * 
     * Executes an SQL statement and returns the number of affected rows.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @return int The number of affected rows.
     * 
     * @see http://php.net/manual/en/pdo.exec.php
     * 
     */
    public function exec($statement)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $affected_rows = $this->pdo->exec($statement);
        $this->endProfile($statement);
        return $affected_rows;
    }
    
    /**
     * 
     * Performs a statement and returns the number of affected rows.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @return int
     * 
     */
    public function fetchAffected($statement, array $values = array())
    {
        $sth = $this->perform($statement, $values);
        return $sth->rowCount();
    }
    
    /**
     * 
     * Fetches a sequential array of rows from the database; the rows
     * are returned as associative arrays.
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
    public function fetchAll(
        $statement,
        array $values = array(),
        $callable = null
    ) {
        $sth = $this->perform($statement, $values);
        $data = $sth->fetchAll(self::FETCH_ASSOC);
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
     * are returned as associative arrays, and the array of rows is keyed
     * on the first column of each row.
     * 
     * N.b.: If multiple rows have the same first column value, the last
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
     * @throws \PDOException if the connection fails.
     * 
     */
    public function fetchAssoc(
        $statement,
        array $values = array(),
        $callable = null
    ) {
        $sth = $this->perform($statement, $values);
        $data = array();
        if ($callable) {
            while ($row = $sth->fetch(self::FETCH_ASSOC)) {
                $key = current($row); // value of the first element
                $data[$key] = call_user_func($callable, $row);
            }
        } else {
            while ($row = $sth->fetch(self::FETCH_ASSOC)) {
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
    public function fetchCol(
        $statement,
        array $values = array(),
        $callable = null
    ) {
        $sth = $this->perform($statement, $values);
        $data = $sth->fetchAll(self::FETCH_COLUMN, 0);
        if ($callable) {
            foreach ($data as $key => $val) {
                $data[$key] = call_user_func($callable, $val);
            }
        }
        return $data;
    }

    /**
     * 
     * Fetches one row from the database as an object where the column values
     * are mapped to object properties.
     * 
     * Warning: PDO "injects property-values BEFORE invoking the constructor -
     * in other words, if your class initializes property-values to defaults
     * in the constructor, you will be overwriting the values injected by
     * fetchObject() !"
     * <http://www.php.net/manual/en/pdostatement.fetchobject.php#111744>
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @param string $class_name The name of the class to create.
     * 
     * @param array $ctor_args Arguments to pass to the object constructor.
     * 
     * @return object
     * 
     */
    public function fetchObject(
        $statement,
        array $values = array(),
        $class_name = 'StdClass',
        array $ctor_args = array()
    ) {
        $sth = $this->perform($statement, $values);
        return $sth->fetchObject($class_name, $ctor_args);
    }

    /**
     * 
     * Fetches a sequential array of rows from the database; the rows
     * are returned as objects where the column values are mapped to
     * object properties.
     * 
     * Warning: PDO "injects property-values BEFORE invoking the constructor -
     * in other words, if your class initializes property-values to defaults
     * in the constructor, you will be overwriting the values injected by
     * fetchObject() !"
     * <http://www.php.net/manual/en/pdostatement.fetchobject.php#111744>
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @param string $class_name The name of the class to create from each
     * row.
     * 
     * @param array $ctor_args Arguments to pass to each object constructor.
     * 
     * @return array
     * 
     */
    public function fetchObjects(
        $statement,
        array $values = array(),
        $class_name = 'StdClass',
        array $ctor_args = array()
    ) {
        $sth = $this->perform($statement, $values);
        return $sth->fetchAll(self::FETCH_CLASS, $class_name, $ctor_args);
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
        $sth = $this->perform($statement, $values);
        return $sth->fetch(self::FETCH_ASSOC);
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
    public function fetchPairs(
        $statement,
        array $values = array(),
        $callable = null
    ) {
        $sth = $this->perform($statement, $values);
        if ($callable) {
            $data = array();
            while ($row = $sth->fetch(self::FETCH_NUM)) {
                // apply the callback first so the key can be modified
                $row = call_user_func($callable, $row);
                // now retain the data
                $data[$row[0]] = $row[1];
            }
        } else {
            $data = $sth->fetchAll(self::FETCH_KEY_PAIR);
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
        $sth = $this->perform($statement, $values);
        return $sth->fetchColumn(0);
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
        $this->connect();
        return $this->pdo->getAttribute($attribute);
    }
    
    /**
     * 
     * Returns the DSN for a lazy connection; if the underlying PDO instance
     * was injected at construction time, this will be null.
     * 
     * @return string|null
     * 
     */
    public function getDsn()
    {
        return $this->dsn;
    }
    
    /**
     * 
     * Returns the underlying PDO connection object.
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
        $result = $this->pdo->inTransaction();
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Is this instance connected to a database?
     * 
     * @return bool
     * 
     */
    public function isConnected()
    {
        return isset($this->pdo);
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
        $result = $this->pdo->lastInsertId($name);
        $this->endProfile();
        return $result;
    }
    
    /**
     * 
     * Performs a query with bound values and returns the resulting
     * PDOStatement; array values will be passed through `quote()` and their
     * respective placeholders will be replaced in the query string.
     * 
     * @param string $statement The SQL statement to perform.
     * 
     * @param array $values Values to bind to the query
     * 
     * @return PDOStatement
     * 
     * @see quote()
     * 
     */
    public function perform($statement, array $values = array())
    {
        $sth = $this->prepareWithValues($statement, $values);
        $this->beginProfile(__FUNCTION__);
        $sth->execute();
        $this->endProfile($statement, $values);
        return $sth;
    }

    /**
     * 
     * Prepares an SQL statement for execution.
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
        return $this->pdo->prepare($statement, $options);
    }

    /**
     * 
     * Queries the database and returns a PDOStatement.
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
     * 
     */
    public function query(
        $statement,
        $fetch_mode = null,
        $fetch_arg1 = null,
        $fetch_arg2 = null
    ) {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        if ($fetch_arg2 !== null) {
            $sth = $this->pdo->query(
                $statement,
                $fetch_mode,
                $fetch_arg1,
                $fetch_arg2
            );
        } elseif ($fetch_arg1 !== null) {
            $sth = $this->pdo->query($statement, $fetch_mode, $fetch_arg1);
        } elseif ($fetch_mode !== null) {
            $sth = $this->pdo->query($statement, $fetch_mode);
        } else {
            $sth = $this->pdo->query($statement);
        }
        $this->endProfile($sth->queryString);
        return $sth;
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
    public function quote($value, $parameter_type = self::PARAM_STR)
    {
        $this->connect();

        // non-array quoting
        if (! is_array($value)) {
            return $this->pdo->quote($value, $parameter_type);
        }
        
        // quote array values, not keys, then combine with commas
        foreach ($value as $k => $v) {
            $value[$k] = $this->pdo->quote($v, $parameter_type);
        }
        return implode(', ', $value);
    }
    
    /**
     * 
     * Rolls back the current transaction, and restores autocommit mode.
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
        $result = $this->pdo->rollBack();
        $this->endProfile();

        return $result;
    }
    
    /**
     * 
     * Sets a PDO attribute value.
     * 
     * @param mixed $attribute The PDO::ATTR_* constant.
     * 
     * @param mixed $value The value for the attribute.
     * 
     * @return bool True on success, false on failure. Note that if PDO has not
     * not connected, all calls will be treated as successful.
     * 
     */
    public function setAttribute($attribute, $value)
    {
        if ($this->pdo) {
            return $this->pdo->setAttribute($attribute, $value);
        }
        
        $this->attributes[$attribute] = $value;
        return true;
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
    }
    
    /**
     * 
     * Ends and records a profile entry.
     * 
     * @param string $statement The statement being profiled, if any.
     * 
     * @return null
     * 
     */
    protected function endProfile($statement = null, array $values = array())
    {
        // is there a profiler in place?
        if ($this->profiler) {
            // add an entry to the profiler
            $this->profiler->addProfile(
                microtime(true) - $this->profile['time'],
                $this->profile['function'],
                $statement,
                $values
            );
        }
        
        // clear the starting profile info
        $this->profile = array();
    }

    /**
     * 
     * Prepares an SQL statement with bound values.
     * 
     * This method only binds values that have placeholders in the
     * statement, thereby avoiding errors from PDO regarding too many bound
     * values. It also binds all sequential (question-mark) placeholders.
     * 
     * If a placeholder value is an array, the array is converted to a string
     * of comma-separated quoted values; e.g., for an `IN (...)` condition.
     * The quoted string is replaced directly into the statement instead of
     * using `PDOStatement::bindValue()` proper.
     * 
     * @param string $statement The SQL statement to prepare for execution.
     * 
     * @return PDOStatement
     * 
     * @see http://php.net/manual/en/pdo.prepare.php
     * 
     */
    public function prepareWithValues($statement, array $values = array())
    {
        // if there are no values to bind ...
        if (! $values) {
            // ... use the normal preparation
            return $this->prepare($statement);
        }

        // rebuild the statement and values
        list($statement, $values) = $this->rebuild($statement, $values);

        // prepare the statement
        $sth = $this->prepare($statement);

        // for the placeholders we found, bind the corresponding data values
        foreach ($values as $key => $val) {
            $sth->bindValue($key, $val);
        }

        // done
        return $sth;
    }
    
    /**
     * 
     * Returns a new anonymous object to track bind values.
     * 
     * @param array $values The values to bind and/or replace into a statement.
     * 
     * @return object
     * 
     */
    protected function newBindTracker($values)
    {
        // anonymous object to track preparation info
        return (object) array(
            // how many numbered placeholders in the original statement
            'num' => 0,
            // how many numbered placeholders to actually be bound; this may
            // differ from 'num' in that some numbered placeholders may get
            // replaced with quoted CSV strings
            'count' => 0,
            // initial values to be bound
            'values' => $values,
            // named and numbered placeholders to bind at the end
            'final_values' => array(),
        );
    }

    /**
     * 
     * Rebuilds a statement with array values replaced into placeholders.
     * 
     * @param string $statement The statement to rebuild.
     * 
     * @param array $values The values to bind and/or replace into a statement.
     * 
     * @return array An array where element 0 is the rebuilt statement and
     * element 1 is the rebuilt array of values.
     * 
     */
    protected function rebuild($statement, $values)
    {
        $bind = $this->newBindTracker($values);

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

            // split into subparts by ":name" and "?"
            $subs = preg_split(
                "/(:[a-zA-Z_][a-zA-Z0-9_]*)|(\?)/m",
                $parts[$i],
                -1,
                PREG_SPLIT_DELIM_CAPTURE
            );

            // check subparts to convert bound arrays to quoted CSV strings
            $subs = $this->prepareValuePlaceholders($subs, $bind);
            
            // reassemble
            $parts[$i] = implode('', $subs);
        }

        // bring the parts back together in case they were modified
        $statement = implode('', $parts);

        // return the rebuilt statement and final values
        return array($statement, $bind->final_values);
    }

    /**
     * 
     * Prepares the sub-parts of a query with placeholders.
     * 
     * @param array $subs The query subparts.
     * 
     * @param object $bind The preparation info object.
     * 
     * @return array The prepared subparts.
     * 
     */
    protected function prepareValuePlaceholders(array $subs, $bind)
    {
        foreach ($subs as $i => $sub) {
            $char = substr($sub, 0, 1);
            if ($char == '?') {
                $subs[$i] = $this->prepareNumberedPlaceholder($sub, $bind);
            }
            
            if ($char == ':') {
                $subs[$i] = $this->prepareNamedPlaceholder($sub, $bind);
            }
        }
        
        return $subs;
    }
    
    /**
     * 
     * Bind or quote a numbered placeholder in a query subpart.
     * 
     * @param string $sub The query subpart.
     * 
     * @param object $bind The preparation info object.
     * 
     * @return string The prepared query subpart.
     * 
     */
    protected function prepareNumberedPlaceholder($sub, $bind)
    {
        // what numbered placeholder is this in the original statement?
        $bind->num ++;
        
        // is the corresponding data element an array?
        $bind_array = isset($bind->values[$bind->num])
                   && is_array($bind->values[$bind->num]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = $this->quote($bind->values[$bind->num]);
        } else {
            // increase the count of numbered placeholders to be bound
            $bind->count ++;
            $bind->final_values[$bind->count] = $bind->values[$bind->num];
        }
        
        return $sub;
    }
    
    /**
     * 
     * Bind or quote a named placeholder in a query subpart.
     * 
     * @param string $sub The query subpart.
     * 
     * @param object $bind The preparation info object.
     * 
     * @return string The prepared query subpart.
     * 
     */
    protected function prepareNamedPlaceholder($sub, $bind)
    {
        $name = substr($sub, 1);
        
        // is the corresponding data element an array?
        $bind_array = isset($bind->values[$name])
                   && is_array($bind->values[$name]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = $this->quote($bind->values[$name]);
        } else {
            // not an array, retain the placeholder for later
            $bind->final_values[$name] = $bind->values[$name];
        }
        
        return $sub;
    }
}
