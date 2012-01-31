<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;
use Aura\Di\ForgeInterface;

/**
 * 
 * Driver Factory
 * 
 * @package Aura.Sql
 * 
 */
class DriverFactory
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
     * Driver classes.
     * 
     * @var array
     * 
     */
    protected $map = [];
    
    /**
     * 
     * A Driver class to use when no class exists for a mapped name.
     * 
     * @param ForgeInterface $forge A Forge to create objects.
     * 
     * @param array $map A map of PDO types to Driver classes.
     * 
     * @param string $not_found A Driver class to use when no class
     * can be found for a mapped name.
     * 
     */
    public function __construct(
        ForgeInterface $forge,
        array $map = []
    ) {
        $this->forge = $forge;
        $this->map   = $map;
    }
    
    /**
     * 
     * Creates and returns a Driver class based on a PDO type.
     * 
     * @param string $name A PDO type that maps to a Driver class.
     * 
     * @return Driver
     * 
     * @throws Exception\DriverFactory when no mapped class can be found.
     * 
     */
    public function newInstance(
        $name,
        array $params = []
    ) {
        if (isset($this->map[$name])) {
            $class = $this->map[$name];
        } else {
            throw new Exception\DriverFactory("No Driver class mapping found for '$name'.");
        }
        
        return $this->forge->newInstance($class, $params);
    }
}
