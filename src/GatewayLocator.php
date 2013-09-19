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

use IteratorAggregate;

/**
 * 
 * A ServiceLocator implementation for loading and retaining gateway objects.
 * 
 * @package Aura.Sql
 * 
 */
class GatewayLocator implements IteratorAggregate
{
    /**
     * 
     * A registry to retain gateway objects.
     * 
     * @var array
     * 
     */
    protected $registry;

    /**
     * 
     * Tracks whether or not a registry entry has been converted from a 
     * callable to a gateway object.
     * 
     * @var array
     * 
     */
    protected $converted = [];
    
    /**
     * 
     * Constructor.
     * 
     * @param array $registry An array of key-value pairs where the key is the
     * gateway name and the value is a callable that returns a gateway object.
     * 
     */
    public function __construct(array $registry = [])
    {
        foreach ($registry as $name => $spec) {
            $this->set($name, $spec);
        }
    }

    /**
     * 
     * IteratorAggregate: Returns an iterator for this locator.
     * 
     * @return GatewayIterator
     * 
     */
    public function getIterator()
    {
        return new GatewayIterator($this, array_keys($this->registry));
    }

    /**
     * 
     * Sets a gateway into the registry by name.
     * 
     * @param string $name The gateway name.
     * 
     * @param callable $spec A callable that returns a gateway object.
     * 
     * @return void
     * 
     */
    public function set($name, callable $spec)
    {
        $this->registry[$name] = $spec;
        $this->converted[$name] = false;
    }

    /**
     * 
     * Gets a gateway from the registry by name.
     * 
     * @param string $name The gateway to retrieve.
     * 
     * @return AbstractGateway A gateway object.
     * 
     */
    public function get($name)
    {
        if (! isset($this->registry[$name])) {
            throw new Exception\NoSuchGateway($name);
        }

        if (! $this->converted[$name]) {
            $func = $this->registry[$name];
            $this->registry[$name] = $func();
            $this->converted[$name] = true;
        }

        return $this->registry[$name];
    }
}
