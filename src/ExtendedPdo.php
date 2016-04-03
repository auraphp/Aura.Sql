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
class ExtendedPdo extends AbstractExtendedPdo
{
    protected $args = [];

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
     * @param ProfilerInterface $profiler Records query profiles to a log.
     *
     * @see http://php.net/manual/en/pdo.construct.php
     *
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = [],
        array $attributes = [],
        ProfilerInterface $profiler = null
    ) {
        $this->args = [
            $dsn,
            $username,
            $password,
            $options,
            $attributes,
        ];

        if ($profiler === null) {
            $profiler = new Profiler(new NullLogger());
        }

        $this->setProfiler($profiler);
    }

    public function connect()
    {
        if ($this->pdo) {
            return;
        }

        $this->profiler->start(__FUNCTION__);

        list($dsn, $username, $password, $options, $attributes) = $this->args;
        $this->pdo = new PDO($dsn, $username, $password, $options);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach ($attributes as $attribute => $value) {
            $this->pdo->setAttribute($attribute, $value);
        }

        $this->profiler->finish();
    }

    public function disconnect()
    {
        $this->pdo = null;
    }
}
