<?php
namespace Aura\Sql\Query;

class Update extends AbstractQuery
{
    use ValuesTrait;
    use WhereTrait;
    
    protected $table;
    
    public function __toString()
    {
        $values = [];
        foreach ($this->values as $col => $value) {
            $values[] = "{$col} = {$value}";
        }
        
        $where = null;
        if ($this->where) {
            $where .= 'WHERE' . $this->indent($this->where);
        }
        
        return 'UPDATE ' . $this->table . PHP_EOL
             . 'SET' . $this->indentCsv($values)
             . $where;
    }
    
    public function table($table)
    {
        $this->table = $this->sql->quoteNamesIn($table);
        return $this;
    }
}
