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
        // if no error mode is specified, use exceptions
        if (! isset($options[PDO::ATTR_ERRMODE])) {
            $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }

        // sqlsrv fails to connect when the error mode uses exceptions
        $sqlsrvWarnEx = substr($dsn, 0, 7) == 'sqlsrv:'
            && $options[PDO::ATTR_ERRMODE] == PDO::ERRMODE_EXCEPTION;
        if ($sqlsrvWarnEx) {
            $options[PDO::ATTR_ERRMODE] == PDO::ERRMODE_WARNING;
        }

        // retain the arguments for later
        $this->args = [
            $dsn,
            $username,
            $password,
            $options,
            $queries
        ];

        // retain a profiler, instantiating a null profiler if needed
        if ($profiler === null) {
            $profiler = new Profiler(new NullLogger());
        }
        $this->setProfiler($profiler);

        // retain a query parser
        $parts = explode(':', $dsn);
        $parser = $this->newParser($parts[0]);
        $this->setParser($parser);
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

        // connection-time queries
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

    /**
     *
     * The purpose of this method is to hide sensitive data from stack traces.
     *
     * @return array
     *
     */
    public function __debugInfo()
    {
        return [
            'args' => [
                $this->args[0],
                '****',
                '****',
                $this->args[3],
                $this->args[4],
            ]
        ];
    }
}
