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
 * Decorates an existing PDO instance with the extended methods.
 *
 * @package Aura.Sql
 *
 */
class DecoratedPdo extends AbstractExtendedPdo
{
    /**
     *
     * Constructor.
     *
     * This overrides the parent so that it can take an existing PDO instance
     * and decorate it with the extended methods.
     *
     * @param PDO $pdo An existing PDO instance to decorate.
     *
     * @param ProfilerInterface $profiler Tracks and logs query profiles.
     *
     */
    public function __construct(PDO $pdo, ProfilerInterface $profiler = null)
    {
        $this->pdo = $pdo;
        if ($profiler === null) {
            $profiler = new Profiler(new NullLogger());
        }
        $this->setProfiler($profiler);
        $this->rebuilder = new Rebuilder();
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
        // already connected
    }

    /**
     *
     * Disconnects from the database; disallowed with decorated PDO connections.
     *
     * @return null
     *
     */
    public function disconnect()
    {
        $message = "Cannot disconnect a DecoratedPdo instance.";
        throw new Exception\CannotDisconnect($message);
    }
}
