<?php
namespace Aura\Sql;

use SplObjectStorage;
use Exception as PhpException;

class UnitOfWork
{
    // the gateways used for inserting, updating, and deleting data objects
    protected $gateways;
    
    // the various database connections extracted from gateways
    protected $connections;
    
    // a collection of data objects to be sent to the database
    protected $objects;
    
    // a collection of data objects that were successfully inserted
    protected $inserted;
    
    // a collection of data objects that were successfully updated
    protected $udpates;
    
    // a collection of data objects that were successfully deleted
    protected $deleted;
    
    // the exception that occurred during exec(), causing a rollback
    protected $exception;
    
    // the object that caused the exception
    protected $failed;
    
    public function __construct(GatewayLocator $gateways)
    {
        $this->gateways = $gateways;
        $this->objects  = new SplObjectStorage;
    }
    
    // attach an object to the unit of work for insertion
    public function insert($gateway, $object)
    {
        $this->detach($object);
        $this->attach(
            $object,
            [
                'method' => 'execInsert',
                'gateway' => $gateway,
            ]
        );
    }
    
    // attach an object to the unit of work for updating
    public function update($gateway, $new_object, $old_object = null)
    {
        $this->detach($new_object);
        $this->attach(
            $new_object,
            [
                'method' => 'execUpdate',
                'gateway' => $gateway,
                'old_object' => $old_object,
            ]
        );
    }
    
    // attach an object to the unit of work for deletion
    public function delete($gateway, $object)
    {
        $this->detach($object);
        $this->attach(
            $object,
            [
                'method' => 'execDelete',
                'gateway' => $gateway,
            ]
        );
    }
    
    // attach an object to the unit of work
    protected function attach($object, $info)
    {
        $this->objects->attach($object, $info);
    }
    
    // detach an object from the unit of work
    public function detach($object)
    {
        $this->objects->detach($object);
    }
    
    public function loadConnections()
    {
        $this->connections = new SplObjectStorage;
        foreach ($this->gateways as $gateway) {
            $connection = $gateway->getConnections()->getWrite();
            $this->connections->attach($connection);
        }
    }
    
    public function getConnections()
    {
        return $this->connections;
    }
    
    // do we need pre/post hooks, so we can handle things like
    // optimistic/pessimistic locking?
    public function exec()
    {
        // clear tracking properties
        $this->exception = null;
        $this->failed    = null;
        $this->deleted   = new SplObjectStorage;
        $this->inserted  = new SplObjectStorage;
        $this->updated   = new SplObjectStorage;
        
        // load the connections from the gateways for transaction management
        $this->loadConnections();
        
        // perform the unit of work
        try {
            
            $this->execBegin();
            
            foreach ($this->objects as $object) {
                
                // get the info for this object
                $info = $this->objects[$object];
                $method = $info['method'];
                $gateway = $this->gateways->get($info['gateway']);
                
                // remove used info
                unset($info['method']);
                unset($info['gateway']);
                
                // execute the method
                $this->$method($gateway, $object, $info);
                
            }
            
            $this->execCommit();
            return true;
            
        } catch (PhpException $e) {
            $this->failed = $object; // from the loop above
            $this->exception = $e;
            $this->execRollback();
            return false;
        }
    }
    
    protected function execBegin()
    {
        foreach ($this->connections as $connection) {
            $connection->beginTransaction();
        }
    }
    
    protected function execInsert($gateway, $object, $info)
    {
        $last_insert_id = $gateway->insert($object);
        $this->inserted->attach($object, [
            'last_insert_id' => $last_insert_id,
        ]);
    }
    
    protected function execUpdate($gateway, $object, $info)
    {
        $old_object = $info['old_object'];
        $gateway->update($object, $old_object);
        $this->updated->attach($object);
    }
    
    protected function execDelete($gateway, $object, $info)
    {
        $gateway->delete($object);
        $this->deleted->attach($object);
    }
    
    protected function execCommit()
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }
    }
    
    protected function execRollback()
    {
        foreach ($this->connections as $connection) {
            $connection->rollBack();
        }
    }
    
    public function getObjects()
    {
        return $this->objects;
    }
    
    public function getInserted()
    {
        return $this->inserted;
    }
    
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function getDeleted()
    {
        return $this->deleted;
    }
    
    public function getException()
    {
        return $this->exception;
    }
    
    public function getFailed()
    {
        return $this->failed;
    }
}
