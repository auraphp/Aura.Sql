<?php
namespace Aura\Sql\Query;

class Insert extends AbstractQuery
{
    use ValuesTrait;
    
    protected $table;
    
    public function __toString()
    {
        return 'INSERT INTO ' . $this->table . ' ('
             . $this->indentCsv(array_keys($this->values))
             . ') VALUES ('
             . $this->indentCsv(array_values($this->values))
             . ')';
    }
    
    public function into($table)
    {
        $this->table = $this->sql->quoteName($table);
        return $this;
    }
}
