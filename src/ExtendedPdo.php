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
use Psr\Log\NullLogger;

/**
 *
 * A lazy-connecting PDO with extended methods.
 *
 * @package Aura.Sql
 *
 */
class ExtendedPdo extends AbstractExtendedPdo
{
    /**
     *
     * Constructor arguments for instantiating the PDO connection.
     *
     * @var array
     *
     */
    protected $args = [];

    /**
     *
     * Constructor.
     *
     * This overrides the parent so that it can take connection attributes as a
     * constructor parameter, and set them after connection.
     *
     * @param string $dsn The data source name for the connection.
     *
     * @param string $username The username for the connection.
     *
     * @param string $password The password for the connection.
     *
     * @param array $options Driver-specific options for the connection.
     *
     * @param array $attributes Attributes to set after the connection.
     *
     * @param ProfilerInterface $profiler Tracks and logs query profiles.
     *
     * @see http://php.net/manual/en/pdo.construct.php
     *
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = [],
        array $queries = [],
        ProfilerInterface $profiler = null
    ) {
        if (! isset($options[PDO::ATTR_ERRMODE])) {
            $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }

        $this->args = [
            $dsn,
            $username,
            $password,
            $options,
            $queries
        ];

        if ($profiler === null) {
            $profiler = new Profiler(new NullLogger());
        }

        $this->setProfiler($profiler);
    }

    /**
     *
     * Connects to the database.
     *
     * @return null
     *
     */
    public function connect()
    {
        if ($this->pdo) {
            return;
        }

        // connect
        $this->profiler->start(__FUNCTION__);
        list($dsn, $username, $password, $options, $queries) = $this->args;
        $this->pdo = new PDO($dsn, $username, $password, $options);
        $this->profiler->finish();

        // connection-time queries, such as setting charset and collation
        foreach ($queries as $query) {
            $this->exec($query);
        }
    }

    /**
     *
     * Disconnects from the database.
     *
     * @return null
     *
     */
    public function disconnect()
    {
        $this->profiler->start(__FUNCTION__);
        $this->pdo = null;
        $this->profiler->finish();
    }
}
