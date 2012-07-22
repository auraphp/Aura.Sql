<?php
namespace Aura\Sql\Query;

class Delete extends AbstractQuery
{
    use WhereTrait;
    
    protected $table;
    
    public function __toString()
    {
        $where = null;
        if ($this->where) {
            $where .= PHP_EOL . 'WHERE' . $this->indent($this->where);
        }
        
        return 'DELETE FROM ' . $this->table . $where;
    }
    
    public function from($table)
    {
        $this->table = $this->sql->quoteNamesIn($table);
        return $this;
    }
}
