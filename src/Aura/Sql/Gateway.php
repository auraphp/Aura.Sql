<?php
namespace Aura\Sql;

// table data gateway
class Gateway
{
    protected $adapters;
    
    protected $mapper;
    
    public function __construct(
        AdapterLocator $adapters,
        AbstractMapper $mapper
    ) {
        $this->adapters = $adapters;
        $this->mapper   = $mapper;
    }
    
    public function getAdapters()
    {
        return $this->adapters;
    }
    
    public function getMapper()
    {
        return $this->mapper;
    }
    
    public function insert($object)
    {
        $sql = $this->adapters->getWrite();
        $insert = $sql->newInsert();
        $this->mapper->modifyInsert($insert, $object);
        $this->sql->query($insert, $insert->getData());
        return $this->sql->lastInsertId();
    }
    
    public function update($new_object, $old_object = null)
    {
        $sql = $this->adapters->getWrite();
        $update = $sql->newUpdate();
        $this->mapper->modifyUpdate($update, $new_object, $old_object);
        return $this->sql->query($update, $update->getData());
    }
    
    public function delete($object)
    {
        $sql = $this->adapters->getWrite();
        $delete = $sql->newDelete();
        $this->mapper->modifyDelete($delete, $object);
        return $this->sql->query($delete, $delete->getData());
    }
    
    public function fetchAll(Select $select, array $data = [])
    {
        $sql = $select->getSql();
        return $sql->fetchAll($select, $data);
    }
    
    public function fetchCol(Select $select, array $data = [])
    {
        $sql = $select->getSql();
        return $sql->fetchCol($select, $data);
    }
    
    public function fetchOne(Select $select, array $data = [])
    {
        $sql = $select->getSql();
        return $sql->fetchOne($select, $data);
    }
    
    public function fetchPairs(Select $select, array $data = [])
    {
        $sql = $select->getSql();
        return $sql->fetchPairs($select, $data);
    }
    
    public function fetchValue(Select $select, array $data = [])
    {
        $sql = $select->getSql();
        return $sql->fetchValue($select, $data);
    }

    public function newSelect(array $cols = [])
    {
        $sql = $this->adapters->getRead();
        $select = $sql->newSelect();
        $this->mapper->modifySelect($select, $cols);
        return $select;
    }
}
