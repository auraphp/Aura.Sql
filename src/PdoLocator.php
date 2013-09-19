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
 * Manages PDO service objects for default, read, and write connections.
 * 
 * @package Aura.Sql
 * 
 */
class PdoLocator implements PdoLocatorInterface
{
    /**
     * 
     * A registry of PDO service entries.
     * 
     * @var array
     * 
     */
    protected $registry = array(
        'default' => null,
        'read' => array(),
        'write' => array(),
    );

    /**
     * 
     * Whether or not registry entries have been converted to objects.
     * 
     * @var array
     * 
     */
    protected $converted = array(
        'default' => false,
        'read' => array(),
        'write' => array(),
    );
    
    /**
     * 
     * Constructor.
     * 
     * @param callable $default A callable to create a default service.
     * 
     * @param array $read An array of callables to create read services.
     * 
     * @param array $write An array of callables to create write services.
     * 
     */
    public function __construct(
        $default,
        array $read = array(),
        array $write = array()
    ) {
        $this->setDefault($default);
        foreach ($read as $name => $callable) {
            $this->setRead($name, $callable);
        }
        foreach ($write as $name => $callable) {
            $this->setWrite($name, $callable);
        }
    }

    /**
     * 
     * Sets the default service registry entry.
     * 
     * @param callable $callable The registry entry.
     * 
     * @return null
     * 
     */
    public function setDefault($callable)
    {
        $this->registry['default'] = $callable;
        $this->converted['default'] = false;
    }

    /**
     * 
     * Returns the default service object.
     * 
     * @return PdoInterface
     * 
     */
    public function getDefault()
    {
        if (! $this->converted['default']) {
            $callable = $this->registry['default'];
            $this->registry['default'] = call_user_func($callable);
            $this->converted['default'] = true;
        }
        
        return $this->registry['default'];
    }

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
    public function setRead($name, $callable)
    {
        $this->registry['read'][$name] = $callable;
        $this->converted['read'][$name] = false;
    }

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
    public function getRead($name = null)
    {
        return $this->getService('read', $name);
    }

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
    public function setWrite($name, $callable)
    {
        $this->registry['write'][$name] = $callable;
        $this->converted['write'][$name] = false;
    }

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
    public function getWrite($name = null)
    {
        return $this->getService('write', $name);
    }
    
    /**
     * 
     * Returns a service by name.
     * 
     * @param string $type The service type ('read' or 'write').
     * 
     * @param string $name The name of the service.
     * 
     * @return PdoInterface
     * 
     */
    protected function getService($type, $name)
    {
        if (! $this->registry[$type]) {
            return $this->getDefault();
        }
        
        if (! $name) {
            $name = array_rand($this->registry[$type]);
        }
        
        if (! isset($this->registry[$type][$name])) {
            throw new Exception\ServiceNotFound("{$type}:{$name}");
        }
        
        if (! $this->converted[$type][$name]) {
            $callable = $this->registry[$type][$name];
            $this->registry[$type][$name] = call_user_func($callable);
            $this->converted[$type][$name] = true;
        }
        
        return $this->registry[$type][$name];
    }
}
