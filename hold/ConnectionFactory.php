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
 * Adapter Factory
 * 
 * @package Aura.Sql
 * 
 */
class AdapterFactory
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
     * Adapter classes.
     * 
     * @var array
     * 
     */
    protected $map = [];
    
    /**
     * 
     * A Adapter class to use when no class exists for a mapped name.
     * 
     * @param ForgeInterface $forge A Forge to create objects.
     * 
     * @param array $map A map of PDO types to Adapter classes.
     * 
     * @param string $not_found A Adapter class to use when no class
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
     * Creates and returns a Adapter class based on a PDO type.
     * 
     * @param string $name A PDO type that maps to a Adapter class.
     * 
     * @return Adapter
     * 
     * @throws Exception\AdapterFactory when no mapped class can be found.
     * 
     */
    public function newInstance(
        $name,
        array $params = []
    ) {
        if (isset($this->map[$name])) {
            $class = $this->map[$name];
        } else {
            throw new Exception\AdapterFactory("No Adapter class mapping found for '$name'.");
        }
        
        return $this->forge->newInstance($class, $params);
    }
}
