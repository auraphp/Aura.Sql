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
class ProfiledExtendedPdo extends ExtendedPdo
{
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
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.begintransaction.php
     *
     */
    public function beginTransaction()
    {
        $this->beginProfile(__FUNCTION__);
        $result = parent::beginTransaction();
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
        $this->beginProfile(__FUNCTION__);
        $result = parent::commit();
        $this->endProfile();
        return $result;
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
        $this->beginProfile(__FUNCTION__);
        $result = parent::rollBack();
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
        $this->beginProfile(__FUNCTION__);
        $result = parent::inTransaction();
        $this->endProfile();
        return $result;
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
        $this->beginProfile(__FUNCTION__);
        $affected_rows = parent::exec($statement);
        $this->endProfile($statement);
        return $affected_rows;
    }

    /**
     *
     * Returns the last inserted autoincrement sequence value.
     *
     * @param string $name The name of the sequence to check; typically needed
     * only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
     *
     * @return string
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     *
     */
    public function lastInsertId($name = null)
    {
        $this->beginProfile(__FUNCTION__);
        $result = parent::lastInsertId($name);
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
        $this->beginProfile(__FUNCTION__);
        $sth = parent::prepare($statement, $options);
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
    public function query($statement, $fetch_mode = 0, $fetch_arg1 = null, $fetch_arg2 = null)
    {
        $this->beginProfile(__FUNCTION__);
        $sth = parent::query($statement, $fetch_mode, $fetch_arg1, $fetch_arg2);
        $this->endProfile($sth->queryString);
        return $sth;
    }
}
