<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;

use Iterator;

/**
 * 
 * An object to allow iteration over the elements of a GatewayLocator.
 * 
 * @package Aura.Sql
 * 
 */
class GatewayIterator implements Iterator
{
    /**
     * 
     * The gateways over which we are iterating.
     * 
     * @var GatewayLocator
     * 
     */
    protected $gateways;

    /**
     * 
     * The keys to iterate over in the GatewayLocator object.
     * 
     * @var array
     * 
     */
    protected $keys;

    /**
     * 
     * Is the current iterator position valid?
     * 
     * @var bool
     * 
     */
    protected $valid;

    /**
     * 
     * Constructor.
     * 
     * @param GatewayLocator $gateways The GatewayLocator object over which to iterate.
     * 
     * @param array $keys The keys in the GatewayLocator object.
     * 
     */
    public function __construct(GatewayLocator $gateways, array $keys = [])
    {
        $this->gateways = $gateways;
        $this->keys = $keys;
    }

    /**
     * 
     * Returns the value at the current iterator position.
     * 
     * @return Gateway
     * 
     */
    public function current()
    {
        return $this->gateways->get($this->key());
    }

    /**
     * 
     * Returns the current iterator position.
     * 
     * @return string
     * 
     */
    public function key()
    {
        return current($this->keys);
    }

    /**
     * 
     * Moves the iterator to the next position.
     * 
     * @return void
     * 
     */
    public function next()
    {
        $this->valid = (next($this->keys) !== false);
    }

    /**
     * 
     * Moves the iterator to the first position.
     * 
     * @return void
     * 
     */
    public function rewind()
    {
        $this->valid = (reset($this->keys) !== false);
    }

    /**
     * 
     * Is the current iterator position valid?
     * 
     * @return void
     * 
     */
    public function valid()
    {
        return $this->valid;
    }
}
