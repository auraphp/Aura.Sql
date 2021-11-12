<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;

use PDO;
use PDOStatement;

/**
 *
 * An interface to the native PDO object.
 *
 * @package Aura.Sql
 *
 */
interface PdoInterface
{
    /**
     *
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.begintransaction.php
     *
     */
    public function beginTransaction(): bool;

    /**
     *
     * Commits the existing transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.commit.php
     *
     */
    public function commit(): bool;

    /**
     *
     * Gets the most recent error code.
     *
     * @return string|null
     */
    public function errorCode(): ?string;

    /**
     *
     * Gets the most recent error info.
     *
     * @return array
     *
     */
    public function errorInfo(): array;

    /**
     *
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string $statement The SQL statement to execute.
     *
     * @return int|false The number of rows affected.
     *
     * @see http://php.net/manual/en/pdo.exec.php
     *
     */
    public function exec(string $statement);

    /**
     *
     * Gets a PDO attribute value.
     *
     * @param int $attribute The PDO::ATTR_* constant.
     *
     * @return bool|int|string|array|null The value for the attribute.
     *
     */
    public function getAttribute(int $attribute);

    /**
     *
     * Is a transaction currently active?
     *
     * @return bool
     *
     * @see http://php.net/manual/en/pdo.intransaction.php
     *
     */
    public function inTransaction(): bool;

    /**
     *
     * Returns the last inserted autoincrement sequence value.
     *
     * @param string|null $name The name of the sequence to check; typically needed
     * only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
     *
     * @return string|false
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     *
     */
    public function lastInsertId(?string $name = null);

    /**
     *
     * Prepares an SQL statement for execution.
     *
     * @param string $query The SQL statement to prepare for execution.
     *
     * @param array $options Set these attributes on the returned
     * PDOStatement.
     *
     * @return \PDOStatement|false
     *
     * @see http://php.net/manual/en/pdo.prepare.php
     */
    public function prepare(string $query, array $options = []);

    /**
     *
     * Queries the database and returns a PDOStatement.
     *
     * @param string $query The SQL statement to prepare and execute.
     *
     * @param mixed ...$fetch Optional fetch-related parameters.
     *
     * @return \PDOStatement|false
     *
     * @see http://php.net/manual/en/pdo.query.php
     *
     */
    public function query(string $query, ...$fetch);

    /**
     *
     * Quotes a value for use in an SQL statement.
     *
     * @param string|int|array|float|null $value The value to quote.
     *
     * @param int $type A data type hint for the database driver.
     *
     * @return string|false The quoted value.
     *
     * @see http://php.net/manual/en/pdo.quote.php
     *
     */
    public function quote($value, int $type = PDO::PARAM_STR);

    /**
     *
     * Rolls back the current transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.rollback.php
     *
     */
    public function rollBack(): bool;

    /**
     *
     * Sets a PDO attribute value.
     *
     * @param int $attribute The PDO::ATTR_* constant.
     *
     * @param mixed $value The value for the attribute.
     *
     * @return bool
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
}
