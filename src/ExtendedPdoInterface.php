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

/**
 * 
 * An interface to the Aura.Sql extended PDO object.
 * 
 * @package Aura.Sql
 * 
 */
interface ExtendedPdoInterface
{
    // =======================================================================
    // Native PDO methods (some with overrides)
    // =======================================================================
    
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
    public function beginTransaction();
    
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
    public function commit();
    
    /**
     * 
     * Gets the most recent error code.
     * 
     * @return mixed
     * 
     */
    public function errorCode();
    
    /**
     * 
     * Gets the most recent error info.
     * 
     * @return array
     * 
     */
    public function errorInfo();
    
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
    public function exec($statement);
    
    /**
     * 
     * Gets a PDO attribute value.
     * 
     * @param mixed $attribute The PDO::ATTR_* constant.
     * 
     * @return mixed The value for the attribute.
     * 
     */
    public function getAttribute($attribute);
    
    /**
     * 
     * Is a transaction currently active?
     * 
     * @return bool
     * 
     * @see http://php.net/manual/en/pdo.intransaction.php
     * 
     */
    public function inTransaction();
    
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
    public function lastInsertId($name = null);
    
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
    public function prepare($statement, $options = null);
    
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
    public function query($statement, $fetch_mode = null, $fetch_arg1 = null, $fetch_arg2 = null);
    
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
    public function quote($value, $parameter_type = ExtendedPdo::PARAM_STR);
    
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
    public function rollBack();
    
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
    public function setAttribute($attribute, $value);
    
    /**
     * 
     * Returns all currently available PDO drivers.
     * 
     * @return array
     * 
     */
    public static function getAvailableDrivers();


    // =======================================================================
    // Extended PDO methods
    // =======================================================================
    
    /**
     * 
     * Retains a single value to bind to the next query statement; it will
     * be merged with existing bound values, and will be reset after the
     * next query.
     * 
     * @param string $name The parameter name.
     * 
     * @param mixed $value The parameter value.
     * 
     * @return null
     * 
     */
    public function bindValue($name, $value);
    
    /**
     * 
     * Retains several values to bind to the next query statement; these will
     * be merged with existing bound values, and will be reset after the
     * next query.
     * 
     * @param array $bind_values An array where the key is the parameter name and
     * the value is the parameter value.
     * 
     * @return null
     * 
     */
    public function bindValues(array $bind_values);
    
    /**
     * 
     * Connects to the database and sets PDO attributes.
     * 
     * @return null
     * 
     * @throws PDOException if the connection fails.
     * 
     */
    public function connect();
    
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
    public function fetchAll($statement, array $values = array(), $callable = null);

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
    public function fetchAssoc($statement, array $values = array(), $callable = null);
    
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
    public function fetchCol($statement, array $values = array(), $callable = null);
    
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
    public function fetchOne($statement, array $values = array());
    
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
    public function fetchPairs($statement, array $values = array(), $callable = null);
    
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
    public function fetchValue($statement, array $values = array());
    
    /**
     * 
     * Returns the array of values to bind to the next query.
     * 
     * @return array
     * 
     */
    public function getBindValues();

    /**
     * 
     * Returns the DSN for the connection.
     * 
     * @return string
     * 
     */
    public function getDsn();

    /**
     * 
     * Returns the name of the driver from the DSN.
     * 
     * @return string
     * 
     */
    public function getDriver();
    
    /**
     * 
     * Returns the profiler object.
     * 
     * @return ProfilerInterface
     * 
     */
    public function getProfiler();
    
    /**
     * 
     * Is the instance connected to a database?
     * 
     * @return bool
     * 
     */
    public function isConnected();
    
    /**
     * 
     * Sets the profiler object.
     * 
     * @param ProfilerInterface $profiler
     * 
     * @return null
     * 
     */
    public function setProfiler(ProfilerInterface $profiler);
}
