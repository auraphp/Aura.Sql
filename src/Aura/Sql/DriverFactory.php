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
 * A factory for driver objects.
 * 
 * @package Aura.Sql
 * 
 */
class DriverFactory
{
    /**
     * 
     * A map of short driver names to fully-qualified classes.
     * 
     * @var array
     * 
     */
    protected $map = [
        'mysql'  => 'Aura\Sql\Driver\Mysql',
        'pgsql'  => 'Aura\Sql\Driver\Pgsql',
        'sqlite' => 'Aura\Sql\Driver\Sqlite',
    ];
    
    /**
     * 
     * Constructor.
     * 
     * @param array $map An override map of driver names to classes.
     * 
     */
    public function __construct(array $map = [])
    {
        $this->map = array_merge($this->map, $map);
    }
    
    /**
     * 
     * Returns a new driver instance.
     * 
     * @param string $name The name of the driver.
     * 
     * @param mixed $dsn The DSN for the driver connection.
     * 
     * @param string $username The username for the driver connection.
     * 
     * @param string $password The password for the driver connection.
     * 
     * @param array $options PDO options for the driver connection.
     * 
     * @return AbstractDriver
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
        return new $class(
            $profiler,
            $column_factory,
            $dsn,
            $username,
            $password,
            $options
        );
    }
}
