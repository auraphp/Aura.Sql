<?php
namespace Aura\Sql;
class ConnectionManager
{
    protected $default = array(
        'adapter'  => null,
        'dsn'      => array(),
        'username' => null,
        'password' => null,
        'options'  => array(),
    );
    
    protected $masters = array();
    
    protected $slaves = array();
    
    protected $factory;
    
    protected $conn = array(
        'default' => null,
        'masters' => array(),
        'slaves'  => array(),
    );
    
    public function __construct(
        ConnectionFactory $factory,
        array $default = array(),
        array $masters = array(),
        array $slaves = array()
    ) {
        $this->factory = $factory;
        $this->default = $this->merge($default);
        $this->masters = $masters;
        $this->slaves = $slaves;
    }
    
    // pick a random slave, or a random master if no slaves, or default if no masters
    public function getRead()
    {
        if ($slaves) {
            return $this->getRandomSlave();
        } elseif ($masters) {
            return $this->getRandomMaster();
        } else {
            return $this->getDefault();
        }
    }
    
    // pick a random master or the default
    public function getWrite()
    {
        if ($masters) {
            return $this->getRandomMaster();
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
            throw new Exception\NoSuchConnection;
        }
        
        if (! $this->conn['masters'][$key] instanceof Connection) {
            list($adapter, $params) = $this->mergeAdapterParams($this->masters[$key]);
            $this->conn['masters'][$key] = $this->factory->newInstance($adapter, $params);
        }
        return $this->conn['masters'][$key];
    }
    
    // converts a random $this->masters entry to a Connection object
    public function getRandomSlave()
    {
        if (! $key) {
            $key = array_rand($this->slaves);
        } elseif (! isset($this->slaves[$key])) {
            throw new Exception\NoSuchConnection;
        }
        
        if (! $this->conn['slaves'][$key] instanceof Connection) {
            list($adapter, $params) = $this->mergeAdapterParams($this->slaves[$key]);
            $this->conn['slaves'][$key] = $this->factory->newInstance($adapter, $params);
        }
        return $this->conn['slaves'][$key];
    }
    
    // merges $this->default with master or slave override values
    public function mergeAdapterParams(array $override = array())
    {
        $merged  = $this->merge($override);
        $adapter = $merged['adapter'];
        $params  = array(
            'dsn'      => $merged['dsn'],
            'username' => $merged['username'],
            'password' => $merged['password'],
            'options'  => $merged['options'],
        );
        return array($adapter, $params);
    }
    
    public function merge(array $override = array())
    {
        // pre-empt merging if possible
        if (! $override) {
            return $this->default;
        }
        
        // recursively merge, which turns scalars into arrays
        $merged = array_merge_recursive($this->default, $override);
        
        // convert the keys that are supposed to be scalars
        $list = array('adapter', 'username', 'password');
        foreach ($list as $key) {
            if (is_array($merged[$key])) {
                $merged[$key] = end($merged[$key]);
            }
        }
        
        // done!
        return $merged;
    }
}