<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;
use PDOStatement;

/**
 * 
 * Interface for query profilers.
 * 
 * @package Aura.Sql
 * 
 */
interface ProfilerInterface
{
    /**
     * 
     * Turns the profiler on and off.
     * 
     * @param bool $active True to turn on, false to turn off.
     * 
     * @return void
     * 
     */
    public function setActive($active);

    /**
     * 
     * Is the profiler active?
     * 
     * @return bool
     * 
     */
    public function isActive();

    /**
     * 
     * Executes a PDOStatment and profiles it.
     * 
     * @param PDOStatement $stmt The PDOStatement to execute and profile.
     * 
     * @param array $data The data that was bound into the statement.
     * 
     * @return mixed
     * 
     */
    public function exec(PDOStatement $stmt, array $data = []);

    /**
     * 
     * Calls a user function and and profile it.
     * 
     * @param callable $func The user function to call.
     * 
     * @param array $data The data that was used by the function.
     * 
     * @return mixed
     * 
     */
    public function call($func, $text, array $data = []);

    /**
     * 
     * Adds a profile to the profiler.
     * 
     * @param string $text The text (typically an SQL query) being profiled.
     * 
     * @param float $time The elapsed time in seconds.
     * 
     * @param array $data The data that was used.
     * 
     * @param string $trace An exception backtrace as a string.
     * 
     * @return mixed
     * 
     */
    public function addProfile($text, $time, array $data, $trace);

    /**
     * 
     * Returns all the profiles.
     * 
     * @return array
     * 
     */
    public function getProfiles();
}
 