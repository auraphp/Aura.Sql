<?php
namespace Aura\Sql\Query;

trait ValuesTrait
{
    protected $values;
    
    public function col($col)
    {
        $key = $this->sql->quoteName($col);
        $this->values[$key] = ":$col";
    }
    
    public function cols(array $cols)
    {
        foreach ($cols as $col) {
            $this->col($col);
        }
        return $this;
    }
    
    public function set($col, $value)
    {
        if ($value === null) {
            $value = 'NULL';
        }
        
        $key = $this->sql->quoteName($col);
        $value = $this->sql->quoteNamesIn($value);
        $this->values[$key] = $value;
        return $this;
    }
}
