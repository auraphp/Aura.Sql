<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;

use PDOStatement;

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
     * Is the profiler active?
     * 
     * @var bool
     * 
     */
    protected $active = false;

    /**
     * 
     * Retained profiles.
     * 
     * @var array
     * 
     */
    protected $profiles = [];

    /**
     * 
     * Turns the profiler on and off.
     * 
     * @param bool $active True to turn on, false to turn off.
     * 
     * @return void
     * 
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
     * 
     * Is the profiler active?
     * 
     * @return bool
     * 
     */
    public function isActive()
    {
        return (bool) $this->active;
    }

    /**
     * 
     * Executes a PDOStatement and profiles it.
     * 
     * @param PDOStatement $stmt The PDOStatement to execute and profile.
     * 
     * @param array $bind The data that was bound into the statement.
     * 
     * @return mixed
     * 
     */
    public function exec(PDOStatement $stmt, array $bind = [])
    {
        if (! $this->isActive()) {
            return $stmt->execute();
        }

        $before = microtime(true);
        $result = $stmt->execute();
        $after  = microtime(true);
        $e      = new Exception;
        $trace  = $e->getTraceAsString();
        $this->addProfile($stmt->queryString, $after - $before, $bind, $trace);
        return $result;
    }

    /**
     * 
     * Calls a user function and and profile it.
     * 
     * @param callable $func The user function to call.
     * 
     * @param string $text The text of the SQL query.
     * 
     * @param array $bind The data that was used by the function.
     * 
     * @return mixed
     * 
     */
    public function call($func, $text, array $bind = [])
    {
        if (! $this->isActive()) {
            return call_user_func($func);
        }

        $before = microtime(true);
        $result = call_user_func($func);
        $after  = microtime(true);
        $e      = new Exception;
        $trace  = $e->getTraceAsString();
        $this->addProfile($text, $after - $before, $bind, $trace);
        return $result;
    }

    /**
     * 
     * Adds a profile to the profiler.
     * 
     * @param string $text The text (typically an SQL query) being profiled.
     * 
     * @param float $time The elapsed time in seconds.
     * 
     * @param array $bind The data that was used.
     * 
     * @param string $trace An exception backtrace as a string.
     * 
     * @return mixed
     * 
     */
    public function addProfile($text, $time, array $bind, $trace)
    {
        $this->profiles[] = (object) [
            'text' => $text,
            'time' => $time,
            'data' => $bind,
            'trace' => $trace
        ];
    }

    /**
     * 
     * Returns all the profiles.
     * 
     * @return array
     * 
     */
    public function getProfiles()
    {
        return $this->profiles;
    }
}
