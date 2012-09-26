<?php
namespace Aura\Sql;

use SplObjectStorage;

class UnitOfWork
{
    protected $gateways;
    
    protected $connections;
    
    protected $objects;
    
    protected $inserts;
    
    protected $udpates;
    
    protected $deletes;
    
    protected $exception;
    
    // the object that caused the exception
    protected $failed_object;
    
    public function __construct(GatewayLocator $gateways)
    {
        $this->gateways = $gateways;
        $this->objects  = new SplObjectStorage;
    }
    
    // add a object to the unit of work for insertion
    public function insert($object)
    {
        $this->objects->detach($object);
        $this->objects->attach($object, ['method' => 'execInsert']);
    }
    
    // add a object to the unit of work for updating
    public function update($new_object)
    {
        $this->objects->detach($object);
        $this->objects->attach(
            $object,
            [
                'method' => 'execUpdate',
                'old_object' => $old_object,
            ]
        );
    }
    
    // add a object to the unit of work for deletion
    public function delete($object)
    {
        $this->objects->detach($object);
        $this->objects->attach($object, ['method' => 'execDelete']);
    }
    
    // attach a object to the unit of work
    protected function attach($object, $info)
    {
        $this->objects->attach($object, $info);
    }
    
    // detach a object from the unit of work
    public function detach($object)
    {
        $this->objects->detach($object);
    }
    
    public function loadConnections()
    {
        foreach ($this->gateways as $gateway) {
            $connection = $gateway->getConnection();
            if (! in_array($connection, $this->connections, true)) {
                $this->connections[] = $connection;
            }
        }
    }
    
    public function exec()
    {
        // clear tracking properties
        $this->connections   = null;
        $this->exception     = null;
        $this->failed_object = null;
        $this->deletes       = new SplObjectStorage;
        $this->inserts       = new SplObjectStorage;
        $this->updates       = new SplObjectStorage;
        
        // load the connections for the gateways
        $this->loadConnections();
        
        // perform the unit of work
        try {
            
            $this->execBegin();
            
            foreach ($this->objects as $object) {
                
                // locate the gateway for this object
                $class = get_class($object);
                $gateway = $this->gateways->get($class);
                
                // get the method and info for this object
                $info = $this->objects[$object];
                $method = $info['method'];
                unset($info['method']);
                
                // execute the method
                $this->$method($gateway, $object, $info);
                
            }
            
            $this->execCommit();
            return true;
            
        } catch (Exception $e) {
            $this->failed_object = $object; // from the loop above
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
        $this->inserts->attach($object, [
            'last_insert_id' => $last_insert_id,
        ]);
    }
    
    protected function execUpdate($gateway, $object, $info)
    {
        $old_object = $info['old_object'];
        $gateway->update($object, $old_object);
        $this->updates->attach($object);
    }
    
    protected function execDelete($gateway, $object, $info)
    {
        $gateway->delete($object);
        $this->deletes->attach($object);
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
    
    public function getInserts()
    {
        return $this->inserts;
    }
    
    public function getUpdates()
    {
        return $this->updates;
    }
    
    public function getDeletes()
    {
        return $this->deletes;
    }
    
    public function getException()
    {
        return $this->exception;
    }
    
    public function getFailedObject()
    {
        return $this->failed_object;
    }
}
