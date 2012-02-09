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
 * Columns of table
 * 
 * @package Aura.Sql
 * 
 */
class Column
{
    protected $name;
    
    protected $type; 
    
    protected $size;
    
    protected $scope;
    
    protected $notnull;
    
    protected $default;
    
    protected $autoinc;
        
    protected $primary;
    
    public function __construct(
        $name,
        $type, 
        $size,
        $scope,
        $notnull,
        $default,
        $autoinc,
        $primary
    ) {
        $this->name     = $name;
        $this->type     = $type; 
        $this->size     = $size;
        $this->scope    = $scope;
        $this->notnull  = (bool) $notnull;
        $this->default  = $default;
        $this->autoinc  = (bool) $autoinc;
        $this->primary  = (bool) $primary;
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
