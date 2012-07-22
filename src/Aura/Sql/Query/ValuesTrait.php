<?php
namespace Aura\Sql\Query;

trait ValuesTrait
{
    protected $values;
    
    public function cols($cols)
    {
        foreach ($cols as $col) {
            $this->values[$col] = ":$col";
        }
        return $this;
    }
    
    public function set($col, $value)
    {
        if ($value === null) {
            $value = 'NULL';
        }
        
        $this->values[$col] = $value;
        return $this;
    }
}
