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
 * A factory for adapter objects.
 * 
 * @package Aura.Sql
 * 
 */
class AdapterFactory
{
    /**
     * 
     * A map of short adapter names to fully-qualified classes.
     * 
     * @var array
     * 
     */
    protected $map = [
        'mysql'  => 'Aura\Sql\Adapter\Mysql',
        'pgsql'  => 'Aura\Sql\Adapter\Pgsql',
        'sqlite' => 'Aura\Sql\Adapter\Sqlite',
    ];
    
    /**
     * 
     * Constructor.
     * 
     * @param array $map An override map of adapter names to classes.
     * 
     */
    public function __construct(array $map = [])
    {
        $this->map = array_merge($this->map, $map);
    }
    
    /**
     * 
     * Returns a new adapter instance.
     * 
     * @param string $name The name of the adapter.
     * 
     * @param mixed $dsn The DSN for the adapter.
     * 
     * @param string $username The username for the adapter.
     * 
     * @param string $password The password for the adapter.
     * 
     * @param array $options PDO options for the adapter.
     * 
     * @return AbstractAdapter
     * 
     */
    public function newInstance(
        $name,
        $dsn,
        $username = null,
        $password = null,
        $options = []
    ) {
        $class = $this->map[$name];
        $profiler = new Profiler;
        $column_factory = new ColumnFactory;
        $select_factory = new SelectFactory;
        return new $class(
            $profiler,
            $column_factory,
            $select_factory,
            $dsn,
            $username,
            $password,
            $options
        );
    }
}
