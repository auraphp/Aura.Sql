<?php
namespace Aura\Sql;

// table data gateway
class Gateway
{
    protected $sql;
    
    protected $mapper;
    
    public function __construct(
        AbstractAdapter $sql,
        AbstractMapper  $mapper
    ) {
        $this->sql = $sql;
        $this->mapper = $mapper;
    }
    
    public function getSql()
    {
        return $this->sql;
    }
    
    public function getMapper()
    {
        return $this->mapper;
    }
    
    public function fetch(array $identity_values)
    {
        $select = $this->newSelect();
        $select->where(
            $this->mapper->getTablePrimaryCol() . ' IN (?)',
            $identity_values
        );
        return $this->sql->fetchAll($select);
    }
    
    public function insert($object)
    {
        $insert = $this->sql->newInsert();
        $this->mapper->modifyInsert($insert, $object);
        $this->sql->query($insert, $insert->getData());
        return $this->sql->lastInsertId();
    }
    
    public function update($new_object, $old_object = null)
    {
        $update = $this->sql->newUpdate();
        $this->mapper->modifyUpdate($update, $new_object, $old_object);
        return $this->sql->query($update, $update->getData());
    }
    
    public function delete($object)
    {
        $delete = $this->sql->newDelete();
        $this->mapper->modifyDelete($delete, $object);
        return $this->sql->query($delete, $delete->getData());
    }
    
    public function fetchAll(Select $select, array $data = [])
    {
        return $this->sql->fetchAll($select, $data);
    }
    
    public function fetchCol(Select $select, array $data = [])
    {
        return $this->sql->fetchCol($select, $data);
    }
    
    public function fetchOne(Select $select, array $data = [])
    {
        return $this->sql->fetchOne($select, $data);
    }
    
    public function fetchPairs(Select $select, array $data = [])
    {
        return $this->sql->fetchPairs($select, $data);
    }
    
    public function fetchValue(Select $select, array $data = [])
    {
        return $this->sql->fetchValue($select, $data);
    }
    
    public function newSelect(array $cols = [])
    {
        $select = $this->sql->newSelect();
        $this->mapper->modifySelect($select, $cols);
        return $select;
    }
}
