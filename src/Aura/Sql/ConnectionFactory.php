<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;

/**
 * 
 * Connection Factory
 * 
 * @package Aura.Sql
 * 
 */
use Aura\Di\ForgeInterface;

class ConnectionFactory
{
    /**
     * 
     * A Forge to create objects.
     * 
     * @var Aura\Di\ForgeInterface
     * 
     */
    protected $forge;
    
    /**
     * 
     * A map of names (called at the command line) to their corresponding
     * Connection classes.
     * 
     * @var array
     * 
     */
    protected $map = array();
    
    /**
     * 
     * A Connection class to use when no class exists for a mapped name.
     * 
     * @param ForgeInterface $forge A Forge to create objects.
     * 
     * @param array $map A map of PDO types to Connection classes.
     * 
     * @param string $not_found A Connection class to use when no class
     * can be found for a mapped name.
     * 
     */
    public function __construct(
        ForgeInterface $forge,
        array $map = array()
    ) {
        $this->forge = $forge;
        $this->map   = $map;
    }
    
    /**
     * 
     * Creates and returns a Connection class based on a PDO type.
     * 
     * @param string $name A PDO type that maps to a Connection class.
     * 
     * @return Connection
     * 
     * @throws Exception\ConnectionFactory when no mapped class can be found.
     * 
     */
    public function newInstance(
        $name,
        array $params = array()
    ) {
        if (isset($this->map[$name])) {
            $class = $this->map[$name];
        } else {
            throw new Exception\ConnectionFactory("No Connection class mapping found for '$name'.");
        }
        
        return $this->forge->newInstance($class, $params);
    }
}
