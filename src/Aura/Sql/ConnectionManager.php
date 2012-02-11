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
 * Manages connections to master and slave databases.
 * 
 * @package Aura.Sql
 * 
 */
class ConnectionManager
{
    protected $default = [
        'adapter'  => null,
        'dsn'      => [],
        'username' => null,
        'password' => null,
        'options'  => [],
    ];
    
    protected $masters = [];
    
    protected $slaves = [];
    
    protected $adapter_factory;
    
    protected $conn = [
        'default' => null,
        'masters' => [],
        'slaves'  => [],
    ];
    
    public function __construct(
        AdapterFactory $adapter_factory,
        array $default = [],
        array $masters = [],
        array $slaves  = []
    ) {
        $this->adapter_factory = $adapter_factory;
        $this->setDefault($default);
        foreach ($masters as $name => $params) {
            $this->setMaster($name, $params);
        }
        foreach ($slaves as $name => $params) {
            $this->setSlave($name, $params);
        }
    }
    
    public function setDefault(array $params)
    {
        $this->default = array_merge($this->default, $params);
    }
    
    public function setMaster($name, array $params)
    {
        $this->masters[$name] = $params;
    }
    
    public function setSlave($name, array $params)
    {
        $this->slaves[$name] = $params;
    }
    
    // pick a random slave, or a random master if no slaves, or default if no masters
    public function getRead()
    {
        if ($this->slaves) {
            return $this->getSlave();
        } elseif ($this->masters) {
            return $this->getMaster();
        } else {
            return $this->getDefault();
        }
    }
    
    // pick a random master or the default
    public function getWrite()
    {
        if ($this->masters) {
            return $this->getMaster();
        } else {
            return $this->getDefault();
        }
    }
    
    // converts $this->default to a Adapter object and returns it
    public function getDefault()
    {
        if (! $this->conn['default'] instanceof AbstractAdapter) {
            $params = $this->mergeParams();
            $this->conn['default'] = $this->adapter_factory->newInstance(
                $params['adapter'],
                $params['dsn'],
                $params['username'],
                $params['password'],
                $params['options']
            );
        }
        return $this->conn['default'];
    }
    
    // converts a $this->masters entry to a Adapter object and returns is
    public function getMaster($key = null)
    {
        if (! $key) {
            $key = array_rand($this->masters);
        } elseif (! isset($this->masters[$key])) {
            throw new Exception\NoSuchMaster($key);
        }
        
        $is_conn = isset($this->conn['masters'][$key])
                && $this->conn['masters'][$key] instanceof AbstractAdapter;
                
        if (! $is_conn) {
            $params = $this->mergeParams($this->masters[$key]);
            $this->conn['masters'][$key] = $this->adapter_factory->newInstance(
                $params['adapter'],
                $params['dsn'],
                $params['username'],
                $params['password'],
                $params['options']
            );
        }
        
        return $this->conn['masters'][$key];
    }
    
    // converts a random $this->slave entry to a Adapter object
    public function getSlave($key = null)
    {
        if (! $key) {
            $key = array_rand($this->slaves);
        } elseif (! isset($this->slaves[$key])) {
            throw new Exception\NoSuchSlave($key);
        }
        
        $is_conn = isset($this->conn['slaves'][$key])
                && $this->conn['slaves'][$key] instanceof AbstractAdapter;
        
        if (! $is_conn) {
            $params = $this->mergeParams($this->slaves[$key]);
            $this->conn['slaves'][$key] = $this->adapter_factory->newInstance(
                $params['adapter'],
                $params['dsn'],
                $params['username'],
                $params['password'],
                $params['options']
            );
        }
        return $this->conn['slaves'][$key];
    }
    
    // merges $this->default with master or slave override values
    protected function mergeParams(array $override = [])
    {
        return $this->merge($this->default, $override);
    }
    
    protected function merge($baseline, $override)
    {
        foreach ($override as $key => $val) {
            if (array_key_exists($key, $baseline) && is_array($val)) {
                $baseline[$key] = $this->merge($baseline[$key], $override[$key]);
            } else {
                $baseline[$key] = $val;
            }
        }

        return $baseline;
    }
}
