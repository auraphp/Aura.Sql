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

/**
 * 
 * Locates PDO services for default, read, and write databases.
 * 
 * @package Aura.Sql
 * 
 */
interface PdoLocatorInterface
{
    /**
     * 
     * Sets the default service registry entry.
     * 
     * @param callable $callable The registry entry.
     * 
     * @return null
     * 
     */
    public function setDefault($callable);
    
    /**
     * 
     * Returns the default service object.
     * 
     * @return PdoInterface
     * 
     */
    public function getDefault();

    /**
     * 
     * Sets a read service registry entry by name.
     * 
     * @param string $name The name of the registry entry.
     * 
     * @param callable $callable The registry entry.
     * 
     * @return null
     * 
     */
    public function setRead($name, $callable);

    /**
     * 
     * Returns a read service by name; if no name is given, picks a
     * random service; if no read services are present, returns the
     * default service.
     * 
     * @param string $name The read service name to return.
     * 
     * @return PdoInterface
     * 
     */
    public function getRead($name = null);

    /**
     * 
     * Sets a write service registry entry by name.
     * 
     * @param string $name The name of the registry entry.
     * 
     * @param callable $callable The registry entry.
     * 
     * @return null
     * 
     */
    public function setWrite($name, $callable);

    /**
     * 
     * Returns a write service by name; if no name is given, picks a
     * random service; if no write services are present, returns the
     * default service.
     * 
     * @param string $name The write service name to return.
     * 
     * @return PdoInterface
     * 
     */
    public function getWrite($name = null);
}
