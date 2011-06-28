<?php
namespace Aura\Sql;

class Column
{
    protected $name;
    protected $type;
    protected $size;
    protected $scope;
    protected $default;
    protected $require;
    protected $primary;
    protected $autoinc;
    
    public function __get($key)
    {
        return $this->$key;
    }
    
    public function __set($key, $val)
    {
        $this->$key = $val;
    }
}
