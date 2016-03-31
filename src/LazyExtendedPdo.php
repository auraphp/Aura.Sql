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
 * This extended decorator for PDO provides lazy connection
 *
 * @package Aura.Sql
 *
 */
class LazyExtendedPdo extends ExtendedPdo
{
    /**
     *
     * The PDO connection itself.
     *
     * @var ExtendedPdo
     *
     */
    protected $pdo = null;

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
     * The username for a lazy connection.
     *
     * @var string
     *
     */
    protected $username;

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
     * PDO options for a lazy connection.
     *
     * @var array
     *
     */
    protected $options = [];

    /**
     *
     * The attributes for a lazy connection.
     *
     * @var array
     *
     */
    protected $attributes = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     *
     * Creates a new PDO when connect() is called
     *
     * @var callable
     *
     */
    private $pdo_factory;

    /**
     *
     * Pass a normal set of PDO constructor parameters and LazyExtendedPdo will use them
     * for a lazy connection.
     *
     * @param string $dsn        The data source name for a lazy PDO connection.
     *
     * @param string $username   The username for a lazy connection.
     *
     * @param string $password   The password for a lazy connection.
     *
     * @param array  $options    Driver-specific options for a lazy connection.
     *
     * @param array  $attributes Attributes to set after a lazy connection.
     *
     * @see http://php.net/manual/en/pdo.construct.php
     *
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = [],
        array $attributes = []
    ) {
        $this->dsn        = $dsn;
        $this->username   = $username;
        $this->password   = $password;
        $this->options    = $options;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     *
     * When connect() is called, it will call $factory($dsn,$username,$password,$options) and expect
     * a PDO to be returned. If no factory is provided, an ExtendedPdo will be created
     *
     * @param callable $factory
     *
     */
    public function setPdoFactory(callable $factory)
    {
        $this->pdo_factory = $factory;
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
        return $this->pdo->beginTransaction();
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
        return $this->pdo->inTransaction();
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
        return $this->pdo->commit();
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
        return $this->pdo->rollBack();
    }

    /**
     *
     * Connects to the database and sets PDO attributes.
     *
     * @return null
     *
     * @throws \PDOException if the connection fails.
     *
     */
    public function connect()
    {
        // don't connect twice
        if ($this->pdo) {
            return;
        }

        // connect to the database
        if ($this->pdo_factory) {
            $this->pdo = call_user_func(
                $this->pdo_factory,
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );
        } else {
            $this->pdo = new ExtendedPdo(
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );
        }
        // set attributes
        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }
    }

    /**
     *
     * Explicitly disconnect by unsetting the PDO instance; does not prevent
     * later reconnection, whether implicit or explicit.
     *
     * @return null
     *
     * @throws Exception\CannotDisconnect when the PDO instance was injected
     * for decoration; manage the lifecycle of that PDO instance elsewhere.
     *
     */
    public function disconnect()
    {
        $this->pdo = null;
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
     * Returns the underlying PDO connection object.
     *
     * @return PDO|null
     *
     */
    public function getPdo()
    {
        return $this->pdo;
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
        return $this->pdo->exec($statement);
    }

    /**
     *
     * Sets a PDO attribute value.
     *
     * @param mixed $attribute The PDO::ATTR_* constant.
     *
     * @param mixed $value     The value for the attribute.
     *
     * @return bool True on success, false on failure. Note that if PDO has not
     * not connected, all calls will be treated as successful.
     *
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
        if (!$this->isConnected()) {
            return true;
        }
        return $this->pdo->setAttribute($attribute, $value);
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
     * Returns the DSN for a lazy connection
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
     * Returns the last inserted autoincrement sequence value.
     *
     * @param string $name The name of the sequence to check; typically needed
     *                     only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
     *
     * @return string
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     *
     */
    public function lastInsertId($name = null)
    {
        $this->connect();
        return $this->pdo->lastInsertId($name);
    }

    /**
     *
     * Performs a query with bound values and returns the resulting
     * PDOStatement; array values will be passed through `quote()` and their
     * respective placeholders will be replaced in the query string.
     *
     * @param string $statement The SQL statement to perform.
     *
     * @param array  $values    Values to bind to the query
     *
     * @return PDOStatement
     *
     * @see quote()
     *
     */
    public function perform($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->perform($statement, $values);
    }

    /**
     *
     * Prepares an SQL statement for execution.
     *
     * @param string $statement The SQL statement to prepare for execution.
     *
     * @param array  $options   Set these attributes on the returned
     *                          PDOStatement.
     *
     * @return PDOStatement
     *
     * @see http://php.net/manual/en/pdo.prepare.php
     *
     */
    public function prepare($statement, $options = [])
    {
        $this->connect();
        return $this->pdo->prepare($statement, $options);
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
     * @param array  $values    The values to bind to the statement, if any.
     *
     * @return PDOStatement
     *
     * @see http://php.net/manual/en/pdo.prepare.php
     *
     */
    public function prepareWithValues($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->prepareWithValues($statement, $values);
    }

    /**
     *
     * Queries the database and returns a PDOStatement.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param mixed ...$fetch Optional fetch-related parameters.
     *
     * @return PDOStatement
     *
     * @see http://php.net/manual/en/pdo.query.php
     *
     */
    public function query($statement, ...$fetch)
    {
        $this->connect();
        return $this->pdo->query($statement, ...$fetch);
    }

    /**
     *
     * Quotes a value for use in an SQL statement.
     *
     * This differs from `PDO::quote()` in that it will convert an array into
     * a string of comma-separated quoted values.
     *
     * @param mixed $value          The value to quote.
     *
     * @param int   $parameter_type A data type hint for the database driver.
     *
     * @return string The quoted value.
     *
     * @see http://php.net/manual/en/pdo.quote.php
     *
     */
    public function quote($value, $parameter_type = PDO::PARAM_STR)
    {
        $this->connect();
        return $this->pdo->quote($value, $parameter_type);
    }

    /**
     *
     * Performs a statement and returns the number of affected rows.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return int
     *
     */
    public function fetchAffected($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchAffected($statement, $values);
    }

    /**
     *
     * Fetches a sequential array of rows from the database; the rows
     * are represented as associative arrays.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchAll($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchAll($statement, $values);
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
     * @param array  $values    Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchAssoc($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchAssoc($statement, $values);
    }

    /**
     *
     * Fetches the first column of rows as a sequential array.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchCol($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchCol($statement, $values);
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
     * @param string $statement  The SQL statement to prepare and execute.
     *
     * @param array  $values     Values to bind to the query.
     *
     * @param string $class_name The name of the class to create.
     *
     * @param array  $ctor_args  Arguments to pass to the object constructor.
     *
     * @return object
     *
     */
    public function fetchObject($statement, array $values = [], $class_name = 'StdClass', array $ctor_args = [])
    {
        $this->connect();
        return $this->pdo->fetchObject($statement, $values, $class_name, $ctor_args);
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
     * @param string $statement  The SQL statement to prepare and execute.
     *
     * @param array  $values     Values to bind to the query.
     *
     * @param string $class_name The name of the class to create from each
     *                           row.
     *
     * @param array  $ctor_args  Arguments to pass to each object constructor.
     *
     * @return array
     *
     */
    public function fetchObjects($statement, array $values = [], $class_name = 'stdClass', array $ctor_args = [])
    {
        $this->connect();
        return $this->pdo->fetchObjects($statement, $values, $class_name, $ctor_args);
    }

    /**
     *
     * Fetches one row from the database as an associative array.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchOne($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchOne($statement, $values);
    }

    /**
     *
     * Fetches an associative array of rows as key-value pairs (first
     * column is the key, second column is the value).
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return array
     *
     */
    public function fetchPairs($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchPairs($statement, $values);
    }

    /**
     *
     * Fetches the very first value (i.e., first column of the first row).
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return mixed
     *
     */
    public function fetchValue($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->fetchValue($statement, $values);
    }

    /**
     *
     * Fetches multiple from the database as an associative array.
     * The first column will be the index
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @param int    $style     a fetch style defaults to PDO::FETCH_COLUMN for single
     *                          values, use PDO::FETCH_NAMED when fetching a multiple columns
     *
     * @return array
     *
     */
    public function fetchGroup($statement, array $values = [], $style = \PDO::FETCH_COLUMN)
    {
        $this->connect();
        return $this->pdo->fetchGroup($statement, $values, $style);
    }

    /**
     *
     * Yields rows from the database
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return \Generator
     *
     */
    public function yieldAll($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->yieldAll($statement, $values);
    }

    /**
     *
     * Yields rows from the database keyed on the first column of each row.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return \Generator
     *
     */
    public function yieldAssoc($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->yieldAssoc($statement, $values);
    }

    /**
     *
     * Yields the first column of all rows
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return \Generator
     *
     */
    public function yieldCol($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->yieldCol($statement, $values);
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
     * @param string $statement  The SQL statement to prepare and execute.
     *
     * @param array  $values     Values to bind to the query.
     *
     * @param string $class_name The name of the class to create from each
     *                           row.
     *
     * @param array  $ctor_args  Arguments to pass to each object constructor.
     *
     * @return \Generator
     *
     */
    public function yieldObjects($statement, array $values = [], $class_name = 'stdClass', array $ctor_args = [])
    {
        $this->connect();
        return $this->pdo->yieldObjects($statement, $values, $class_name, $ctor_args);
    }

    /**
     *
     * Yields key-value pairs (first column is the key, second column is the value).
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array  $values    Values to bind to the query.
     *
     * @return \Generator
     *
     */
    public function yieldPairs($statement, array $values = [])
    {
        $this->connect();
        return $this->pdo->yieldPairs($statement, $values);
    }
}
