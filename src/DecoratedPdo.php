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
use Psr\Log\NullLogger;

/**
 *
 * Provides array quoting, a new `perform()` method, new `fetch*()` methods,
 * and new `yield*()` methods.
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
     * @param ProfilerInterface $profiler Records query profiles to a log.
     *
     */
    public function __construct(PDO $pdo, ProfilerInterface $profiler = null)
    {
        $this->pdo = $pdo;
        if ($profiler === null) {
            $profiler = new Profiler(new NullLogger());
        }
        $this->setProfiler($profiler);
    }

    public function connect()
    {
        // already connected
    }

    public function disconnect()
    {
        $message = "Cannot disconnect a DecoratedPdo instance.";
        throw new Exception\CannotDisconnect($message);
    }
}
