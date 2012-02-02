<?php
namespace Aura\Sql;
class DriverFactory
{
    protected $map = [
        'mysql'  => 'Aura\Sql\Driver\Mysql',
        'pgsql'  => 'Aura\Sql\Driver\Pgsql',
        'sqlite' => 'Aura\Sql\Driver\Sqlite',
    ];
    
    public function __construct(array $map = [])
    {
        $this->map = array_merge($this->map, $map);
    }
    
    public function newInstance(
        $type,
        $dsn,
        $username = null,
        $password = null,
        $options = []
    ) {
        $class = $this->map[$type];
        $profiler = new Profiler;
        return new $class($profiler, $dsn, $username, $password, $options);
    }
}
