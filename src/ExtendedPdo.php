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
 * - Bind values. You may provide values for binding to the next query using
 *   bindValues(). Multiple calls to bindValues() will merge, not reset, the
 *   values. The values will be reset after calling query(), exec(),
 *   prepare(), or any of the fetch*() methods.
 * 
 * - Fetch methods. The class provides several fetch*() methods to reduce
 *   boilerplate code elsewhere. This means you can call, e.g., fetchAll()
 *   directly on the instance instead of having to prepare a statement, bind
 *   values, execute, and then fetch from the prepared statement. All of the
 *   fetch*() methods take an array of values to bind to to the query.
 * 
 * By default, it starts in the ERRMODE_EXCEPTION instead of ERRMODE_SILENT.
 * 
 */
class ExtendedPdo extends PDO implements ExtendedPdoInterface
{
    /**
     * 
     * The PDO instance attributes.
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
     * Executes an SQL statement and returns the number of affected rows.
     * 
     * @param string $statement The SQL statement to prepare and execute.
     * 
     * @return int
     * 
     * @see http://php.net/manual/en/pdo.exec.php
     * 
     */
    public function exec($statement)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $affected_rows = parent::exec($statement);
        $this->endProfile($statement);
        return $affected_rows;
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
    public function fetchCol($statement, array $values = array(), $callable = null)
    {
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
     * Fetches one row from the database as an object, mapping column values
     * to object properties.
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
     * are represented as objects, where the column values are mapped to
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
    public function fetchPairs($statement, array $values = array(), $callable = null)
    {
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
        return parent::getAttribute($attribute);
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
        $result = parent::inTransaction();
        $this->endProfile();
        return $result;
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
     * Performs a query and returns a PDOStatement.
     * 
     * @param string $statement The SQL statement to perform.
     * 
     * @param array $values Values to bind to the query.
     * 
     * @return PDOStatement
     * 
     */
    public function perform($statement, array $values = array())
    {
        $this->bind_values = $values;
        $sth = $this->performPrepare($statement);
        $this->beginProfile(__FUNCTION__);
        $sth->execute();
        $this->endProfile($sth->queryString);
        $this->bind_values = array();
        return $sth;
    }

    /**
     * 
     * Connects to the database and prepares an SQL statement for execution.
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
        return parent::prepare($statement, $options);
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
            $sth = parent::query($statement, $fetch_mode, $fetch_arg1, $fetch_arg2);
        } elseif ($fetch_arg1 !== null) {
            $sth = parent::query($statement, $fetch_mode, $fetch_arg1);
        } elseif ($fetch_mode !== null) {
            $sth = parent::query($statement, $fetch_mode);
        } else {
            $sth = parent::query($statement);
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
            return parent::quote($value, $parameter_type);
        }
        
        // quote array values, not keys, then combine with commas
        foreach ($value as $k => $v) {
            $value[$k] = parent::quote($v, $parameter_type);
        }
        return implode(', ', $value);
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
        if ($this->connected) {
            return parent::setAttribute($attribute, $value);
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
        $this->profile['bind_values'] = $this->bind_values;
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
    protected function endProfile($statement = null)
    {
        // if there's no profiler, can't profile
        if (! $this->profiler) {
            return;
        }
        
        // add an entry to the profiler
        $this->profiler->addProfile(
            microtime(true) - $this->profile['time'],
            $this->profile['function'],
            $statement,
            $this->profile['bind_values']
        );
        
        // clear the starting profile info
        $this->profile = array();
    }
    
    /**
     * 
     * Connects to the database and prepares an SQL statement to be executed,
     * using values that been bound for the next query.
     * 
     * This method only binds values that have named placeholders in the
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
    protected function performPrepare($statement)
    {
        // are there any bind values?
        if (! $this->bind_values) {
            return $this->prepare($statement);
        }

        // anonymous object to track preparation info
        $prep = (object) array(
            // how many numbered placeholders in the original statement
            'num' => 0,
            // how many numbered placeholders to actually be bound; this may
            // differ from 'num' in that some numbered placeholders may get
            // replaced with quoted CSV strings
            'count' => 0,
            // named and numbered placeholders to bind at the end
            'bind_values' => array(),
        );
        
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
            $subs = $this->prepareSubparts($subs, $prep);
            
            // reassemble
            $parts[$i] = implode('', $subs);
        }

        // bring the parts back together in case they were modified
        $statement = implode('', $parts);

        // prepare the statement
        $sth = $this->prepare($statement);

        // for the placeholders we found, bind the corresponding data values,
        // along with all sequential values for question marks
        foreach ($prep->bind_values as $key => $val) {
            $sth->bindValue($key, $val);
        }

        // done
        return $sth;
    }
    
    /**
     * 
     * Prepares the sub-parts of a query with placeholders.
     * 
     * @param array $subs The query subparts.
     * 
     * @param object $prep The preparation info object.
     * 
     * @return array The prepared subparts.
     * 
     */
    protected function prepareSubparts(array $subs, $prep)
    {
        foreach ($subs as $i => $sub) {
            $char = substr($sub, 0, 1);
            if ($char == '?') {
                $subs[$i] = $this->prepareNumberedPlaceholder($sub, $prep);
            }
            
            if ($char == ':') {
                $subs[$i] = $this->prepareNamedPlaceholder($sub, $prep);
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
     * @param object $prep The preparation info object.
     * 
     * @return string The prepared query subpart.
     * 
     */
    protected function prepareNumberedPlaceholder($sub, $prep)
    {
        // what numbered placeholder is this in the original statement?
        $prep->num ++;
        
        // is the corresponding data element an array?
        $bind_array = isset($this->bind_values[$prep->num])
                   && is_array($this->bind_values[$prep->num]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = $this->quote($this->bind_values[$prep->num]);
        } else {
            // increase the count of numbered placeholders to be bound
            $prep->count ++;
            $prep->bind_values[$prep->count] = $this->bind_values[$prep->num];
        }
        
        return $sub;
    }
    
    /**
     * 
     * Bind or quote a named placeholder in a query subpart.
     * 
     * @param string $sub The query subpart.
     * 
     * @param object $prep The preparation info object.
     * 
     * @return string The prepared query subpart.
     * 
     */
    protected function prepareNamedPlaceholder($sub, $prep)
    {
        $name = substr($sub, 1);
        
        // is the corresponding data element an array?
        $bind_array = isset($this->bind_values[$name])
                   && is_array($this->bind_values[$name]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = $this->quote($this->bind_values[$name]);
        } else {
            // not an array, retain the placeholder for later
            $prep->bind_values[$name] = $this->bind_values[$name];
        }
        
        return $sub;
    }
}
