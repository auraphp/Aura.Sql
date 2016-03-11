<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql;

use Aura\Sql\Exception;
use PDO;
use PDOStatement;

/**
 *
 * This decorator for PDO provides array quoting, a new `perform()` method, new
 * new `fetch*()` methods, and new `yield*()` methods.
 *
 * @package Aura.Sql
 *
 */
class DecoratedPdo extends PDO implements ExtendedPdoInterface
{
    /**
     *
     * The PDO connection itself.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     *
     * The current profile information.
     *
     * @var array
     *
     */
    protected $profile = [];

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
     * A specialized statement preparer.
     *
     * @var Rebuilder
     *
     */
    protected $rebuilder;

    /**
     *
     * This constructor is pseudo-polymorphic. You may pass a normal set of PDO
     * constructor parameters, and ExtendedPdo will use them for a lazy
     * connection. Alternatively, if the `$dsn` parameter is an existing PDO
     * instance, that instance will be decorated by ExtendedPdo; the remaining
     * parameters will be ignored.
     *
     * @param PDO An existing instance of PDO.
     *
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
     * A placeholder method for lazy PDO connections.
     *
     * @return null
     *
     * @throws Exception\CannotReconnect if previously disconnected.
     *
     */
    public function connect()
    {
        if ($this->pdo) {
            return;
        }

        $message = "Cannot reconnect after disconnection.";
        throw new Exception\CannotReconnect($message);
    }

    /**
     *
     * Explicitly disconnect by unsetting the PDO instance.
     *
     * @return null
     *
     */
    public function disconnect()
    {
        $this->pdo = null;
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
    public function fetchAffected($statement, array $values = [])
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
     * @return array
     *
     */
    public function fetchAll($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        return $sth->fetchAll(self::FETCH_ASSOC);
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
     * @return array
     *
     */
    public function fetchAssoc($statement, array $values = [])
    {
        $sth  = $this->perform($statement, $values);
        $data = [];
        while ($row = $sth->fetch(self::FETCH_ASSOC)) {
            $data[current($row)] = $row;
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
     * @return array
     *
     */
    public function fetchCol($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        return $sth->fetchAll(self::FETCH_COLUMN, 0);
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
        array $values = [],
        $class_name = 'StdClass',
        array $ctor_args = []
    ) {
        $sth = $this->perform($statement, $values);

        if ($ctor_args) {
            return $sth->fetchObject($class_name, $ctor_args);
        }

        return $sth->fetchObject($class_name);
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
        array $values = [],
        $class_name = 'StdClass',
        array $ctor_args = []
    ) {
        $sth = $this->perform($statement, $values);

        if ($ctor_args) {
            return $sth->fetchAll(self::FETCH_CLASS, $class_name, $ctor_args);
        }

        return $sth->fetchAll(self::FETCH_CLASS, $class_name);
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
    public function fetchOne($statement, array $values = [])
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
     * @param array $values Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchPairs($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        return $sth->fetchAll(self::FETCH_KEY_PAIR);
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
    public function fetchValue($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        return $sth->fetchColumn(0);
    }

    /**
     *
     * Fetches multiple from the database as an associative array.
     * The first column will be the index
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @param int $style a fetch style defaults to PDO::FETCH_COLUMN for single
     *      values, use PDO::FETCH_NAMED when fetching a multiple columns
     *
     * @return array
     *
     */
    public function fetchGroup(
        $statement,
        array $values = [],
        $style = self::FETCH_COLUMN
    ) {
        $sth = $this->perform($statement, $values);
        return $sth->fetchAll(self::FETCH_GROUP | $style);
    }

    /**
     *
     * Yields rows from the database
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return array
     *
     */
    public function yieldAll($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        while ($row = $sth->fetch(self::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     *
     * Yields rows from the database keyed on the first column of each row.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return array
     *
     */
    public function yieldAssoc($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        while ($row = $sth->fetch(self::FETCH_ASSOC)) {
            $key = current($row);
            yield $key => $row;
        }
    }

    /**
     *
     * Yields the first column of all rows
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return array
     *
     */
    public function yieldCol($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        while ($row = $sth->fetch(self::FETCH_NUM)) {
            yield $row[0];
        }
    }

    /**
     *
     * Yields objects where the column values are mapped to object properties.
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
    public function yieldObjects($statement, array $values = [], $class_name = 'StdClass', array $ctor_args = [])
    {
        $sth = $this->perform($statement, $values);

        if ($ctor_args) {
            while ($instance = $sth->fetchObject($class_name, $ctor_args)) {
                yield $instance;
            }
        } else {
            while ($instance = $sth->fetchObject($class_name)) {
                yield $instance;
            }
        }
    }

    /**
     *
     * Yields key-value pairs (first column is the key, second column is the value).
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return array
     *
     */
    public function yieldPairs($statement, array $values = [])
    {
        $sth = $this->perform($statement, $values);
        while ($row = $sth->fetch(self::FETCH_NUM)) {
            yield $row[0] => $row[1];
        }
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
    public function perform($statement, array $values = [])
    {
        $this->connect();
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
    public function prepare($statement, $options = [])
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $sth = $this->pdo->prepare($statement, $options);
        $this->endProfile($statement, $options);
        return $sth;
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
    public function query($statement)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);

        // remove empty constructor params list if it exists
        $args = func_get_args();
        if (count($args) === 4 && $args[3] === []) {
            unset($args[3]);
        }

        $sth = call_user_func_array(array($this->pdo, 'query'), $args);

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
     * @return bool True on success, false on failure.
     *
     */
    public function setAttribute($attribute, $value)
    {
        $this->connect();
        return $this->pdo->setAttribute($attribute, $value);
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
     * @param array $values The values bound to the statement, if any.
     *
     * @return null
     *
     */
    protected function endProfile($statement = null, array $values = [])
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
        $this->profile = [];
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
     * @param array $values The values to bind to the statement, if any.
     *
     * @return PDOStatement
     *
     * @see http://php.net/manual/en/pdo.prepare.php
     *
     */
    public function prepareWithValues($statement, array $values = [])
    {
        // if there are no values to bind ...
        if (! $values) {
            // ... use the normal preparation
            return $this->prepare($statement);
        }

        // rebuild the statement and values
        $rebuilder = new Rebuilder($this);
        list($statement, $values) = $rebuilder->__invoke($statement, $values);

        // prepare the statement
        $sth = $this->prepare($statement);

        // for the placeholders we found, bind the corresponding data values
        foreach ($values as $key => $val) {
            $this->bindValue($sth, $key, $val);
        }

        // done
        return $sth;
    }

    /**
     *
     * Bind a value using the proper PDO::PARAM_* type.
     *
     * @param PDOStatement $sth The statement to bind to.
     *
     * @param mixed $key The placeholder key.
     *
     * @param mixed $val The value to bind to the statement.
     *
     * @return null
     *
     * @throws Exception\CannotBindValue when the value to be bound is not
     * bindable (e.g., array, object, or resource).
     *
     */
    protected function bindValue(PDOStatement $sth, $key, $val)
    {
        if (is_int($val)) {
            return $sth->bindValue($key, $val, self::PARAM_INT);
        }

        if (is_bool($val)) {
            return $sth->bindValue($key, $val, self::PARAM_BOOL);
        }

        if (is_null($val)) {
            return $sth->bindValue($key, $val, self::PARAM_NULL);
        }

        if (! is_scalar($val)) {
            $type = gettype($val);
            throw new Exception\CannotBindValue(
                "Cannot bind value of type '{$type}' to placeholder '{$key}'"
            );
        }

        $sth->bindValue($key, $val);
    }
}
