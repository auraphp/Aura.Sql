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

use Aura\Sql\Query\Select;
use Aura\Sql\Query\Insert;
use Aura\Sql\Query\Update;
use Aura\Sql\Query\Delete;

/**
 * 
 * A TableDataGateway implementation.
 * 
 * @package Aura.Sql
 * 
 */
class Gateway
{
    /**
     * 
     * A ConnectionLocator for database connections.
     * 
     * @var ConnectionLocator
     * 
     */
    protected $connections;

    /**
     * 
     * A mapper between this table gateway and entities.
     * 
     * @var AbstractMapper
     * 
     */
    protected $mapper;

    /**
     * 
     * Constructor.
     * 
     * @param ConnectionLocator $connections A ConnectionLocator for database
     * connections.
     * 
     * @param AbstractMapper $mapper A table-to-entity mapper.
     * 
     */
    public function __construct(
        ConnectionLocator $connections,
        AbstractMapper $mapper
    ) {
        $this->connections = $connections;
        $this->mapper   = $mapper;
    }

    /**
     * 
     * Gets the connection locator.
     * 
     * @return ConnectionLocator
     * 
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * 
     * Gets the mapper.
     * 
     * @return ConnectionLocator
     * 
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * 
     * Inserts an entity into the mapped table using a write connection.
     * 
     * @param object $entity The entity to insert.
     * 
     * @return int The last insert ID.
     * 
     */
    public function insert($entity)
    {
        $connection = $this->connections->getWrite();
        $insert = $connection->newInsert();
        $this->mapper->modifyInsert($insert, $entity);
        $connection->query($insert, $insert->getBind());
        return $connection->lastInsertId();
    }

    /**
     * 
     * Updates an entity in the mapped table using a write connection; if an
     * array of initial data is present, updates only changed values.
     * 
     * @param object $entity The entity to update.
     * 
     * @param array $initial_data Initial data for the entity.
     * 
     * @return bool True if the update succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     * 
     */
    public function update($entity, $initial_data = null)
    {
        $connection = $this->connections->getWrite();
        $update = $connection->newUpdate();
        $this->mapper->modifyUpdate($update, $entity, $initial_data);
        $stmt = $connection->query($update, $update->getBind());
        return (bool) $stmt->rowCount();
    }

    /**
     * 
     * Deletes an entity from the mapped table using a write connection.
     * 
     * @param object $entity The entity to delete.
     * 
     * @return bool True if the delete succeeded, false if not.  (This is
     * determined by checking the number of rows affected by the query.)
     * 
     */
    public function delete($entity)
    {
        $connection = $this->connections->getWrite();
        $delete = $connection->newDelete();
        $this->mapper->modifyDelete($delete, $entity);
        $stmt = $connection->query($delete, $delete->getBind());
        return (bool) $stmt->rowCount();
    }

    /**
     * 
     * Returns a new Select object for the mapped table using a read
     * connection.
     * 
     * @param array $cols Select these columns from the table; when empty,
     * selects all mapped columns.
     * 
     * @return Select
     * 
     */
    public function newSelect(array $cols = [])
    {
        $connection = $this->connections->getRead();
        $select = $connection->newSelect();
        $this->mapper->modifySelect($select, $cols);
        return $select;
    }

    /**
     * 
     * Selects one row from the mapped table for a given column and value(s).
     * 
     * @param string $col The column to use for matching.
     * 
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     * 
     * @return array
     * 
     */
    public function fetchOneBy($col, $val)
    {
        $select = $this->newSelectBy($col, $val);
        return $this->fetchOne($select);
    }

    /**
     * 
     * Selects all rows from the mapped table for a given column and value.
     * 
     * @param string $col The column to use for matching.
     * 
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     * 
     * @return array
     * 
     */
    public function fetchAllBy($col, $val)
    {
        $select = $this->newSelectBy($col, $val);
        return $this->fetchAll($select);
    }

    /**
     * 
     * Creates a Select object to match against a given column and value(s).
     * 
     * @param string $col The column to use for matching.
     * 
     * @param mixed $val The value(s) to match against; this can be an array
     * of values.
     * 
     * @return Select
     * 
     */
    protected function newSelectBy($col, $val)
    {
        $select = $this->newSelect();
        $where = $this->getMapper()->getTableCol($col);
        if (is_array($val)) {
            $where .= ' IN (?)';
        } else {
            $where .= ' = ?';
        }
        $select->where($where, $val);
        return $select;
    }

    /**
     * 
     * Given a Select, fetches all rows.
     * 
     * @param Select $select The Select query object.
     * 
     * @param array $bind Data to bind to the query.
     * 
     * @return array
     * 
     * @see Connection\AbstractConnection::fetchAll()
     * 
     */
    public function fetchAll(Select $select, array $bind = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchAll($select, $bind);
    }

    /**
     * 
     * Given a Select, fetches the first column of all rows.
     * 
     * @param Select $select The Select query object.
     * 
     * @param array $bind Data to bind to the query.
     * 
     * @return array
     * 
     * @see Connection\AbstractConnection::fetchCol()
     * 
     */
    public function fetchCol(Select $select, array $bind = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchCol($select, $bind);
    }

    /**
     * 
     * Given a Select, fetches the first row.
     * 
     * @param Select $select The Select query object.
     * 
     * @param array $bind Data to bind to the query.
     * 
     * @return array
     * 
     * @see Connection\AbstractConnection::fetchOne()
     * 
     */
    public function fetchOne(Select $select, array $bind = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchOne($select, $bind);
    }

    /**
     * 
     * Given a Select, fetches an array of key-value pairs where the first
     * column is the key and the second column is the value.
     * 
     * @param Select $select The Select query object.
     * 
     * @param array $bind Data to bind to the query.
     * 
     * @return array
     * 
     * @see Connection\AbstractConnection::fetchPairs()
     * 
     */
    public function fetchPairs(Select $select, array $bind = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchPairs($select, $bind);
    }

    /**
     * 
     * Given a Select, fetches the first column of the first row.
     * 
     * @param Select $select The Select query object.
     * 
     * @param array $bind Data to bind to the query.
     * 
     * @return mixed
     * 
     * @see Connection\AbstractConnection::fetchValue()
     * 
     */
    public function fetchValue(Select $select, array $bind = [])
    {
        $connection = $select->getConnection();
        return $connection->fetchValue($select, $bind);
    }
}
