<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;

/**
 *
 * Logs query profiles.
 *
 * @package Aura.Sql
 *
 */
interface ProfilerInterface
{
    /**
     *
     * Enable or disable profiler logging.
     *
     * @param bool $active
     *
     */
    public function setActive($active);

    /**
     *
     * Return true if logging is active
     *
     * @return bool
     *
     */
    public function isActive();

    /**
     * @return string
     */
    public function getLogLevel();

    /**
     *
     * Level at which to log profile messages
     *
     * @param string $logLevel Psr\Log\LogLevel constant
     *
     * @return null
     *
     */
    public function setLogLevel($logLevel);

    /**
     * @return string
     */
    public function getMessagePrefix();
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
    public function setMessagePrefix($messagePrefix);

    /**
     *
     * Begins a profile entry.
     *
     * @param string $function The function starting the profile entry.
     *
     * @return null
     *
     */
    public function start($function);

    /**
     *
     * Finished and logs a profile entry.
     *
     * @param string $statement The statement being profiled, if any.
     *
     * @param array $values The values bound to the statement, if any.
     *
     * @return null
     *
     */
    public function finish($statement = null, array $values = []);
}
