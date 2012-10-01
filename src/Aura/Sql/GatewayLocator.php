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
     * Constructor.
     * 
     * @param array $registry An array of key-value pairs where the key is the
     * recode name and the value is the gateway
     * object. The value may also be a closure that returns a gateway object.
     * Note that is has to be a closure, not just any callable, because the
     * gateway object itself might be callable.
     * 
     */
    public function __construct(array $registry = [])
    {
        foreach ($registry as $name => $spec) {
            $this->set($name, $spec);
        }
    }

    public function getIterator()
    {
        return new GatewayIterator($this, array_keys($this->registry));
    }
    
    /**
     * 
     * Sets a gateway into the registry by name.
     * 
     * @param string $name The gateway name, typically the record class for the
     * gateway.
     * 
     * @param string $spec The gateway specification, typically a closure that
     * builds and returns a gateway object.
     * 
     * @return void
     * 
     */
    public function set($name, $spec)
    {
        $this->registry[$name] = $spec;
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

        if ($this->registry[$name] instanceof \Closure) {
            $func = $this->registry[$name];
            $this->registry[$name] = $func();
        }

        return $this->registry[$name];
    }
}
