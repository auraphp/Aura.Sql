<?php
namespace Aura\Sql;

// table data gateway
class Gateway
{
    protected $connections;
    
    protected $mapper;
    
    public function __construct(
        ConnectionLocator $connections,
        AbstractMapper $mapper
    ) {
        $this->connections = $connections;
        $this->mapper   = $mapper;
    }
    
    public function getConnections()
    {
        return $this->connections;
    }
    
    public function getMapper()
    {
        return $this->mapper;
    }
    
    public function insert($object)
    {
        $connection = $this->connections->getWrite();
        $insert = $connection->newInsert();
        $this->mapper->modifyInsert($insert, $object);
        $connection->query($insert, $insert->getData());
        return $connection->lastInsertId();
    }
    
    public function update($new_object, $old_object = null)
    {
        $connection = $this->connections->getWrite();
        $update = $connection->newUpdate();
        $this->mapper->modifyUpdate($update, $new_object, $old_object);
        return $connection->query($update, $update->getData());
    }
    
    public function delete($object)
    {
        $connection = $this->connections->getWrite();
        $delete = $connection->newDelete();
        $this->mapper->modifyDelete($delete, $object);
        return $connection->query($delete, $delete->getData());
    }
    
    public function fetchAll(Select $select, array $data = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchAll($select, $data);
    }
    
    public function fetchCol(Select $select, array $data = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchCol($select, $data);
    }
    
    public function fetchOne(Select $select, array $data = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchOne($select, $data);
    }
    
    public function fetchPairs(Select $select, array $data = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchPairs($select, $data);
    }
    
    public function fetchValue(Select $select, array $data = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchValue($select, $data);
    }

    public function newSelect(array $cols = [])
    {
        $connection = $this->connections->getRead();
        $select = $connection->newSelect();
        $this->mapper->modifySelect($select, $cols);
        return $select;
    }
}
