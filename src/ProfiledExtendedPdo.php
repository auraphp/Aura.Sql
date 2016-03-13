<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql;

use PDOStatement;
use Psr\Log;

/**
 *
 * Use a Psr\Log\LoggerInterface to collect query profile information
 *
 * @package Aura.Sql
 *
 */
class ProfiledExtendedPdo extends ExtendedPdo
{
    /**
     *
     * The current profile information in a stack to allow nesting.
     *
     * @var array
     *
     */
    private $profile = [];

    /**
     *
     * A query logger. See setLogger()
     *
     * @var Log\LoggerInterface
     *
     */
    private $logger;

    /**
     *
     * So you can switch logging on and off. See enableLogging()
     *
     * @var boolean
     *
     */
    private $enabled = false;

    /**
     *
     * The log level for all messages. See setLogLevel()
     *
     * @var string
     *
     */
    private $log_level = Log\LogLevel::DEBUG;

    /**
     *
     * Added in front of each message to help identify several connections in a ConnectionLocator. See setMessagePrefix()
     *
     * @var string
     *
     */
    private $message_prefix = '';

    /**
     *
     * Sets the logger object.
     *
     * @param Log\LoggerInterface $logger
     *
     * @return null
     *
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     *
     * Returns the logger object.
     *
     * @return Log\LoggerInterface
     *
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     *
     * Enable or disable logging
     *
     * @param boolean $isEnabled
     *
     */
    public function enableLogging($isEnabled = true)
    {
        $this->enabled = $isEnabled;
    }

    /**
     *
     * Return true if logging is enabled
     *
     * @return boolean
     */
    public function isLoggingEnabled()
    {
        return !empty($this->logger) && $this->enabled;
    }

    /**
     * @return string
     */
    public function getLogLevel()
    {
        return $this->log_level;
    }

    /**
     *
     * Level at which to log profile messages
     *
     * @param string $log_level Psr\Log\LogLevel constant
     *
     * @return null
     *
     */
    public function setLogLevel($log_level)
    {
        $this->log_level = $log_level;
    }

    /**
     * @return string
     */
    public function getMessagePrefix()
    {
        return $this->message_prefix;
    }

    /**
     *
     * Sets the text to be shown at the start of each logged message to help differentiate multiple connections
     * when using a ConnectionLocator
     *
     * @param string $message_prefix
     *
     * @return null
     *
     */
    public function setMessagePrefix($message_prefix)
    {
        $this->message_prefix = $message_prefix;
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
        $this->beginProfile(__FUNCTION__);
        parent::connect();
        $this->endProfile();
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
        $this->beginProfile(__FUNCTION__);
        parent::disconnect();
        $this->endProfile();
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
     *                     only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
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
     * @param array  $values    Values to bind to the query
     *
     * @return PDOStatement
     *
     * @see quote()
     *
     */
    public function perform($statement, array $values = [])
    {
        $this->beginProfile(__FUNCTION__);
        $sth = parent::perform($statement, $values);
        $this->endProfile($statement, $values);
        return $sth;
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
     * @param mixed  ...$fetch  Optional fetch-related parameters.
     *
     * @return PDOStatement
     *
     * @see http://php.net/manual/en/pdo.query.php
     *
     */
    public function query($statement, ...$fetch)
    {
        $this->beginProfile(__FUNCTION__);
        $sth = parent::query($statement, ...$fetch);
        $this->endProfile($sth->queryString);
        return $sth;
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
        if (! $this->isLoggingEnabled()) {
            return;
        }
        // keep starting information in a stack
        $profile = ['function' => $function, 'start_time' => microtime(true)];
        array_push($this->profile, $profile);
    }

    /**
     *
     * Ends and records a profile entry in the logger.
     *
     * @param string $statement The statement being profiled, if any.
     *
     * @param array  $values    The values bound to the statement, if any.
     *
     * @return null
     *
     */
    protected function endProfile($statement = null, array $values = [])
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }
        $profile = array_pop($this->profile);
        assert(!empty($profile)); // you are missing a call to beginProfile()

        $finishTime             = microtime(true);
        $profile['finish_time'] = $finishTime;
        $profile['duration']    = $finishTime - $profile['start_time'];
        $profile['statement']   = $statement;
        $profile['values']      = $values;
        $profile['context']     = $this->message_prefix;
        $this->logger->log($this->log_level, $this->message_prefix . $profile['function'], $profile);
    }
}
