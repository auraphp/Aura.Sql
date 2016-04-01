<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 *
 * Retains query profiles.
 *
 * @package Aura.Sql
 *
 */
class Profiler implements ProfilerInterface
{
    /**
     *
     * The current profile information in a stack to allow nesting.
     *
     * @var array
     *
     */
    private $stack = [];

    /**
     *
     * Log profile data through this interface.
     *
     * @var LoggerInterface
     *
     */
    private $logger;

    /**
     *
     * So you can switch logging on and off. See enable() and disable().
     *
     * @var bool
     *
     */
    private $active = false;

    /**
     *
     * The log level for all messages. See setLogLevel()
     *
     * @var string
     *
     */
    private $logLevel = LogLevel::DEBUG;

    /**
     *
     * Added in front of each message to help identify several connections
     * in a ConnectionLocator. See setMessagePrefix()
     *
     * @var string
     *
     */
    private $messagePrefix = '';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     *
     * Enable or disable profiler logging.
     *
     * @param bool $active
     *
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
     *
     * Return true if logging is active
     *
     * @return bool
     *
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     *
     * Level at which to log profile messages
     *
     * @param string $logLevel Psr\Log\LogLevel constant
     *
     * @return null
     *
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * @return string
     */
    public function getMessagePrefix()
    {
        return $this->messagePrefix;
    }

    /**
     *
     * Sets the text to be shown at the start of each logged message to help differentiate multiple connections
     * when using a ConnectionLocator
     *
     * @param string $messagePrefix
     *
     * @return null
     *
     */
    public function setMessagePrefix($messagePrefix)
    {
        $this->messagePrefix = $messagePrefix;
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
    public function start($function)
    {
        if (! $this->active) {
            return;
        }

        // keep starting information in a stack
        $profile = ['function' => $function, 'start' => microtime(true)];
        array_push($this->stack, $profile);
    }

    /**
     *
     * Finishes and logs a profile entry.
     *
     * @param string $statement The statement being profiled, if any.
     *
     * @param array $values The values bound to the statement, if any.
     *
     * @return null
     *
     */
    public function finish($statement = null, array $values = [])
    {
        if (! $this->active) {
            return;
        }

        $profile = array_pop($this->stack);
        assert(! empty($profile)); // you are missing a call to begin()

        $finish                 = microtime(true);
        $profile['finish']      = $finish;
        $profile['duration']    = $finish - $profile['start'];
        $profile['statement']   = $statement;
        $profile['values']      = $values;
        $profile['context']     = $this->messagePrefix;
        $this->logger->log(
            $this->logLevel,
            $this->messagePrefix . $profile['function'],
            $profile
        );
    }
}
