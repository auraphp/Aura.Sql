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

use Aura\Sql\Connection\AbstractConnection;

/**
 * 
 * Manages connections to default, master, and slave databases.
 * 
 * @package Aura.Sql
 * 
 */
class ConnectionLocator
{
    /**
     * 
     * An SQL connection factory.
     * 
     * @var ConnectionFactory
     * 
     */
    protected $connection_factory;

    /**
     * 
     * SQL connection connection objects as constructed from their params.
     * 
     * @var array
     * 
     */
    protected $connection = [
        'default' => null,
        'masters' => [],
        'slaves'  => [],
    ];

    /**
     * 
     * The default connection params.
     * 
     * @var array
     * 
     */
    protected $default = [
        'adapter'  => null,
        'dsn'      => [],
        'username' => null,
        'password' => null,
        'options'  => [],
    ];

    /**
     * 
     * Params for one or more master connections. The key for each element in
     * the array is a name for the connection, and each value is an array of
     * connection params (cf. the `$default` array elements).
     * 
     * @var array
     * 
     */
    protected $masters = [];

    /**
     * 
     * Params for one or more slave connections. The key for each element in
     * the array is a name for the connection, and each value is an array of
     * connection params (cf. the $default array elements).
     * 
     * @var array
     * 
     */
    protected $slaves = [];

    /**
     * 
     * Constructor.
     * 
     * @param ConnectionFactory $connection_factory An connection factory to create 
     * connection objects.
     * 
     * @param array $default An array of key-value pairs for the default
     * connection.
     * 
     * @param array $masters An array of key-value pairs where the key is
     * the connection name and the value is an array of connection params.
     * 
     * @param array $slaves An array of key-value pairs where the key is
     * the connection name and the value is an array of connection params.
     * 
     */
    public function __construct(
        ConnectionFactory $connection_factory,
        array $default = [],
        array $masters = [],
        array $slaves  = []
    ) {
        $this->connection_factory = $connection_factory;
        $this->setDefault($default);
        foreach ($masters as $name => $params) {
            $this->setMaster($name, $params);
        }
        foreach ($slaves as $name => $params) {
            $this->setSlave($name, $params);
        }
    }

    /**
     * 
     * Sets the default connection params.
     * 
     * @param array $params The default connection params.
     * 
     * @return void
     * 
     */
    public function setDefault(array $params)
    {
        $this->default = array_merge($this->default, $params);
    }

    /**
     * 
     * Sets the params for one master connection by name.
     * 
     * @param string $name The master connection name.
     * 
     * @param array $params The master connection params.
     * 
     * @return void
     * 
     */
    public function setMaster($name, array $params)
    {
        $this->masters[$name] = $params;
    }

    /**
     * 
     * Sets the params for one slave connection by name.
     * 
     * @param string $name The slave connection name.
     * 
     * @param array $params The slave connection params.
     * 
     * @return void
     * 
     */
    public function setSlave($name, array $params)
    {
        $this->slaves[$name] = $params;
    }

    /**
     * 
     * Returns a "read" connection.  Picks a connection in this order:
     * 
     * - A random slave; or,
     * 
     * - If there are no slaves, a random master; or,
     * 
     * - If there are no masters, the default connection.
     * 
     * @return AbstractConnection
     * 
     */
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

    /**
     * 
     * Returns a "write" connection.  Picks a connection in this order:
     * 
     * - A random master; or,
     * 
     * - If there are no masters, the default connection.
     * 
     * @return AbstractConnection
     * 
     */
    public function getWrite()
    {
        if ($this->masters) {
            return $this->getMaster();
        } else {
            return $this->getDefault();
        }
    }

    /**
     * 
     * Returns the default connection object.
     * 
     * @return AbstractConnection
     * 
     */
    public function getDefault()
    {
        if (! $this->connection['default'] instanceof AbstractConnection) {
            $this->connection['default'] = $this->connection_factory->newInstance(
                $this->default['adapter'],
                $this->default['dsn'],
                $this->default['username'],
                $this->default['password'],
                $this->default['options']
            );
        }
        return $this->connection['default'];
    }

    /**
     * 
     * Returns a "master" connection object by name.
     * 
     * @param string $name The master connection name; if not specified,
     * returns a random master connection.
     * 
     * @return AbstractConnection
     * 
     */
    public function getMaster($name = null)
    {
        if ($name === null) {
            $name = array_rand($this->masters);
        } elseif (! isset($this->masters[$name])) {
            throw new Exception\NoSuchMaster($name);
        }

        $is_conn = isset($this->connection['masters'][$name])
                && $this->connection['masters'][$name] instanceof AbstractConnection;

        if (! $is_conn) {
            $params = $this->merge($this->default, $this->masters[$name]);
            $this->connection['masters'][$name] = $this->connection_factory->newInstance(
                $params['adapter'],
                $params['dsn'],
                $params['username'],
                $params['password'],
                $params['options']
            );
        }

        return $this->connection['masters'][$name];
    }

    /**
     * 
     * Returns a "slave" connection object by name.
     * 
     * @param string $name The slave connection name; if not specified,
     * returns a random slave.
     * 
     * @return AbstractConnection
     * 
     */
    public function getSlave($name = null)
    {
        if ($name === null) {
            $name = array_rand($this->slaves);
        } elseif (! isset($this->slaves[$name])) {
            throw new Exception\NoSuchSlave($name);
        }

        $is_conn = isset($this->connection['slaves'][$name])
                && $this->connection['slaves'][$name] instanceof AbstractConnection;

        if (! $is_conn) {
            $params = $this->merge($this->default, $this->slaves[$name]);
            $this->connection['slaves'][$name] = $this->connection_factory->newInstance(
                $params['adapter'],
                $params['dsn'],
                $params['username'],
                $params['password'],
                $params['options']
            );
        }
        return $this->connection['slaves'][$name];
    }

    /**
     * 
     * A merge function somewhat more friendly than array_merge_recursive()
     * (we need to override sequential values, not append them).
     * 
     * @param array $baseline The baseline values.
     * 
     * @param array $override The override values.
     * 
     * @return array
     * 
     */
    protected function merge($baseline, array $override = [])
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
