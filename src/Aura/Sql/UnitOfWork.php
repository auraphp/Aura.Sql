<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;

use SplObjectStorage;
use Exception as PhpException;

/**
 * 
 * A unit-of-work implementation.
 * 
 * @package Aura.Sql
 * 
 */
class UnitOfWork
{
    /**
     * 
     * A GatewayLocator for the gateways used to insert, update, and delete
     * entity objects.
     * 
     * @var GatewayLocator
     * 
     */
    protected $gateways;

    /**
     * 
     * A collection of database connections extracted from the gateways.
     * 
     * @var SplObjectStorage
     * 
     */
    protected $connections;

    /**
     * 
     * A collection of all entity objects to be sent to the database.
     * 
     * @var SplObjectStorage
     * 
     */
    protected $entities;

    /**
     * 
     * A collection of all entity objects that were successfully inserted.
     * 
     * @var SplObjectStorage
     * 
     */
    protected $inserted;

    /**
     * 
     * A collection of all entity objects that were successfully updated.
     * 
     * @var SplObjectStorage
     * 
     */
    protected $updates;

    /**
     * 
     * A collection of all entity objects that were successfully deleted.
     * 
     * @var SplObjectStorage
     * 
     */
    protected $deleted;

    /**
     * 
     * The exception that occurred during exec(), causing a rollback.
     * 
     * @var PhpException
     * 
     */
    protected $exception;

    /**
     * 
     * The entity object that caused the exception.
     * 
     * @var object
     * 
     */
    protected $failed;

    /**
     * 
     * Constructor.
     * 
     * @param GatewayLocator $gateways The gateway locator.
     * 
     */
    public function __construct(GatewayLocator $gateways)
    {
        $this->gateways = $gateways;
        $this->entities = new SplObjectStorage;
    }

    /**
     * 
     * Attached an entity object for insertion.
     * 
     * @param string $gateway_name The gateway name in the locator.
     * 
     * @param object $entity The entity object to insert.
     * 
     * @return void
     * 
     */
    public function insert($gateway_name, $entity)
    {
        $this->detach($entity);
        $this->attach($entity, [
            'method'       => 'execInsert',
            'gateway_name' => $gateway_name,
        ]);
    }

    /**
     * 
     * Attached an entity object for updating.
     * 
     * @param string $gateway_name The gateway name in the locator.
     * 
     * @param object $entity The entity object to update.
     * 
     * @param array $initial_data Initial data for the entity.
     * 
     * @return void
     * 
     */
    public function update($gateway_name, $entity, array $initial_data = null)
    {
        $this->detach($entity);
        $this->attach($entity, [
            'method'       => 'execUpdate',
            'gateway_name' => $gateway_name,
            'initial_data' => $initial_data,
        ]);
    }

    /**
     * 
     * Attached an entity object for deletion.
     * 
     * @param string $gateway_name The gateway name in the locator.
     * 
     * @param object $entity The entity object to delete.
     * 
     * @return void
     * 
     */
    public function delete($gateway_name, $entity)
    {
        $this->detach($entity);
        $this->attach($entity, [
            'method'       => 'execDelete',
            'gateway_name' => $gateway_name,
        ]);
    }

    /**
     * 
     * Attaches an entity to this unit of work.
     * 
     * @param object $entity The entity to attach.
     * 
     * @param array $info Information about what to do with the entity.
     * 
     * @return void
     * 
     */
    protected function attach($entity, $info)
    {
        $this->entities->attach($entity, $info);
    }

    /**
     * 
     * Detaches an entity from this unit of work.
     * 
     * @param object $entity The entity to detach.
     * 
     * @return void
     * 
     */
    public function detach($entity)
    {
        $this->entities->detach($entity);
    }

    /**
     * 
     * Loads all database connections from the gateways.
     * 
     * @return void
     * 
     */
    public function loadConnections()
    {
        $this->connections = new SplObjectStorage;
        foreach ($this->gateways as $gateway) {
            $connection = $gateway->getConnections()->getWrite();
            $this->connections->attach($connection);
        }
    }

    /**
     * 
     * Gets the collection of database connections.
     * 
     * @return SplObjectStorage
     * 
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * 
     * Executes the unit of work.
     * 
     * @return bool True if the unit succeeded, false if not.
     * 
     * @todo Add pre/post hooks, so we can handle things like optimistic and
     * pessimistic locking?
     * 
     */
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

            foreach ($this->entities as $entity) {

                // get the info for this entity
                $info = $this->entities[$entity];
                $method = $info['method'];
                $gateway = $this->gateways->get($info['gateway_name']);

                // remove used info
                unset($info['method']);
                unset($info['gateway']);

                // execute the method
                $this->$method($gateway, $entity, $info);
            }

            $this->execCommit();
            return true;

        } catch (PhpException $e) {
            $this->failed = $entity; // from the loop above
            $this->exception = $e;
            $this->execRollback();
            return false;
        }
    }

    /**
     * 
     * Begins a transaction on all connections.
     * 
     * @return void
     * 
     */
    protected function execBegin()
    {
        foreach ($this->connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     * 
     * Inserts an entity via a gateway.
     * 
     * @param Gateway $gateway Insert using this gateway.
     * 
     * @param object $entity Insert this entity.
     * 
     * @param array $info Information about the operation.
     * 
     * @return void
     * 
     */
    protected function execInsert(Gateway $gateway, $entity, array $info)
    {
        $last_insert_id = $gateway->insert($entity);
        $this->inserted->attach($entity, [
            'last_insert_id' => $last_insert_id,
        ]);
    }

    /**
     * 
     * Updates an entity via a gateway.
     * 
     * @param Gateway $gateway Update using this gateway.
     * 
     * @param object $entity Update this entity.
     * 
     * @param array $info Information about the operation.
     * 
     * @return void
     * 
     */
    protected function execUpdate(Gateway $gateway, $entity, array $info)
    {
        $initial_data = $info['initial_data'];
        $gateway->update($entity, $initial_data);
        $this->updated->attach($entity);
    }

    /**
     * 
     * Deletes an entity via a gateway.
     * 
     * @param Gateway $gateway Delete using this gateway.
     * 
     * @param object $entity Delete this entity.
     * 
     * @param array $info Information about the operation.
     * 
     * @return void
     * 
     */
    protected function execDelete(Gateway $gateway, $entity, array $info)
    {
        $gateway->delete($entity);
        $this->deleted->attach($entity);
    }

    /**
     * 
     * Commits the transactions on all connections.
     * 
     * @return void
     * 
     */
    protected function execCommit()
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }
    }

    /**
     * 
     * Rolls back the transactions on all connections.
     * 
     * @return void
     * 
     */
    protected function execRollback()
    {
        foreach ($this->connections as $connection) {
            $connection->rollBack();
        }
    }

    /**
     * 
     * Gets all the attached entities.
     * 
     * @return SplObjectStorage
     * 
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * 
     * Gets all the inserted entities.
     * 
     * @return SplObjectStorage
     * 
     */
    public function getInserted()
    {
        return $this->inserted;
    }

    /**
     * 
     * Gets all the updated entities.
     * 
     * @return SplObjectStorage
     * 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * 
     * Gets all the deleted entities.
     * 
     * @return SplObjectStorage
     * 
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * 
     * Gets the exception that caused a rollback in exec().
     * 
     * @return PhpException
     * 
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * 
     * Gets the entity that caused the exception in exec().
     * 
     * @return object
     * 
     */
    public function getFailed()
    {
        return $this->failed;
    }
}
