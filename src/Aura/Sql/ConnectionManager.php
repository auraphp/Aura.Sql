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

/**
 * 
 * Connection Manager
 * 
 */
use Aura\Sql\Exception\NoSuchMaster as NoSuchMasterException;
use Aura\Sql\Exception\NoSuchSlave as NoSuchSlaveException;

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
    
    protected $factory;
    
    protected $conn = [
        'default' => null,
        'masters' => [],
        'slaves'  => [],
    ];
    
    public function __construct(
        ConnectionFactory $factory,
        array $default = [],
        array $masters = [],
        array $slaves = []
    ) {
        $this->factory = $factory;
        $this->default = $default;
        $this->masters = $masters;
        $this->slaves = $slaves;
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
    
    // converts $this->default to a Connection object and returns it
    public function getDefault()
    {
        if (! $this->conn['default'] instanceof Connection) {
            list($adapter, $params) = $this->mergeAdapterParams();
            $this->conn['default'] = $this->factory->newInstance($adapter, $params);
        }
        return $this->conn['default'];
    }
    
    // converts a $this->masters entry to a Connection object and returns is
    public function getMaster($key = null)
    {
        if (! $key) {
            $key = array_rand($this->masters);
        } elseif (! isset($this->masters[$key])) {
            throw new NoSuchMasterException($key);
        }
        
        $is_conn = ! empty($this->conn['masters'][$key])
                && $this->conn['masters'][$key] instanceof Connection;
                
        if (! $is_conn) {
            list($adapter, $params) = $this->mergeAdapterParams($this->masters[$key]);
            $this->conn['masters'][$key] = $this->factory->newInstance($adapter, $params);
        }
        
        return $this->conn['masters'][$key];
    }
    
    // converts a random $this->masters entry to a Connection object
    public function getSlave($key = null)
    {
        if (! $key) {
            $key = array_rand($this->slaves);
        } elseif (! isset($this->slaves[$key])) {
            throw new NoSuchSlaveException($key);
        }
        
        $is_conn = ! empty($this->conn['slaves'][$key])
                && $this->conn['slaves'][$key] instanceof Connection;
        
        if (! $is_conn) {
            list($adapter, $params) = $this->mergeAdapterParams($this->slaves[$key]);
            $this->conn['slaves'][$key] = $this->factory->newInstance($adapter, $params);
        }
        return $this->conn['slaves'][$key];
    }
    
    // merges $this->default with master or slave override values
    protected function mergeAdapterParams(array $override = [])
    {
        $merged  = $this->merge($this->default, $override);
        $adapter = $merged['adapter'];
        $params  = [
            'dsn'      => $merged['dsn'],
            'username' => $merged['username'],
            'password' => $merged['password'],
            'options'  => $merged['options'],
        ];
        return [$adapter, $params];
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
