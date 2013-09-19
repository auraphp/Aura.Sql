<?php
/**
 * 
 * This file is part of Aura for PHP.
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

    public function addProfile(
        $duration,
        $function,
        $statement,
        array $bind_values
    );
    
    /**
     * 
     * Returns all the profiles.
     * 
     * @return array
     * 
     */
    public function getProfiles();
}
