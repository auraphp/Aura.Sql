<?php
namespace Aura\Sql;
class ColumnFactory
{
    protected $name;
    
    protected $type; 
    
    protected $size;
    
    protected $scope;
    
    protected $default;
    
    protected $notnull;
    
    protected $primary;
    
    protected $autoinc;
        
    public function newInstance(
        $name,
        $type, 
        $size,
        $scope,
        $default,
        $notnull,
        $primary,
        $autoinc
    ) {
        $this->name     = $name;
        $this->type     = $type; 
        $this->size     = $size;
        $this->scope    = $scope;
        $this->default  = $default;
        $this->notnull  = (bool) $notnull;
        $this->primary  = (bool) $primary;
        $this->autoinc  = (bool) $autoinc;
    }
    
    public function __get($key)
    {
        return $this->$key;
    }
    
    public function isNullable()
    {
        return ! $this->notnull;
    }
    
    public function isRequired()
    {
        return $this->notnull;
    }
    
    public function hasDefault()
    {
        return $this->default !== null;
    }
}
