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
 * This extended decorator for PDO provides array quoting, a
 * new `perform()` method, and new `fetch*()` methods.
 *
 * @package Aura.Sql
 *
 */
class ExtendedPdo extends PDO implements ExtendedPdoInterface
{
    /**
     *
     * Constructor.
     *
     * This overrides the parent so that it can take connection attributes as a
     * constructor parameter, and set them after connection.
     *
     * @param string $dsn The data source name for the PDO connection.
     *
     * @param string $username The username for the connection.
     *
     * @param string $password The password for the connection.
     *
     * @param array $options Driver-specific options for the connection.
     *
     * @param array $attributes Attributes to set after the connection.
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
        parent::__construct($dsn, $username, $password, $options);

        $attributes = array_replace(
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            $attributes
        );

        foreach ($attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }
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
     *
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
        $class_name = 'stdClass',
        array $ctor_args = []
    ) {
        $sth = $this->perform($statement, $values);

        if (!empty($ctor_args)) {
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
     *
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
        $class_name = 'stdClass',
        array $ctor_args = []
    ) {
        $sth = $this->perform($statement, $values);

        if (! empty($ctor_args)) {
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
     * Fetches multiple from the database as an associative array. The first
     * column will be the index key.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @param int $style a fetch style defaults to PDO::FETCH_COLUMN for single
     * values, use PDO::FETCH_NAMED when fetching a multiple columns
     *
     * @return array
     *
     */
    public function fetchGroup(
        $statement,
        array $values = [],
        $style = PDO::FETCH_COLUMN
    ) {
        $sth = $this->perform($statement, $values);
        return $sth->fetchAll(self::FETCH_GROUP | $style);
    }

    /**
     *
     * Yields rows from the database.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return \Generator
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
     * @return \Generator
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
     * Yields the first column of each row.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return \Generator
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
     * @return \Generator
     *
     */
    public function yieldObjects(
        $statement,
        array $values = [],
        $class_name = 'stdClass',
        array $ctor_args = []
    ) {
        $sth = $this->perform($statement, $values);

        if (empty($ctor_args)) {
            while ($instance = $sth->fetchObject($class_name)) {
                yield $instance;
            }
        } else {
            while ($instance = $sth->fetchObject($class_name, $ctor_args)) {
                yield $instance;
            }
        }
    }

    /**
     *
     * Yields key-value pairs (first column is the key, second column is the
     * value).
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param array $values Values to bind to the query.
     *
     * @return \Generator
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
        $sth = $this->prepareWithValues($statement, $values);
        $sth->execute();
        return $sth;
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
        return parent::query($statement, ...$fetch);
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
     * @return string The quoted value.
     *
     * @see http://php.net/manual/en/pdo.quote.php
     *
     */
    public function quote($value, $parameter_type = self::PARAM_STR)
    {
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
        if (empty($values)) {
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
     * @return boolean
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

        return $sth->bindValue($key, $val);
    }
}
