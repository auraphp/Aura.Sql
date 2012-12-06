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

use Aura\Sql\Query\Delete;
use Aura\Sql\Query\Insert;
use Aura\Sql\Query\Select;
use Aura\Sql\Query\Update;

/**
 * 
 * An abstract class to map entity fields to table columns.
 *  
 * @package Aura.Sql
 * 
 */
abstract class AbstractMapper
{
    /**
     * 
     * The SQL table this mapper works with.
     * 
     * @var string
     * 
     */
    protected $table;

    /**
     * 
     * A map of table columns to entity fields.
     * 
     * @var array
     * 
     */
    protected $cols_fields = [];

    /**
     * 
     * The primary column in the table (maps to the identity field.)
     * 
     * @var string
     * 
     */
    protected $primary_col;

    /**
     * 
     * The identity field in the entity (maps to the primary column).
     * 
     * @var string
     * 
     */
    protected $identity_field;

    /**
     * 
     * Returns the list of table columns.
     * 
     * @return array
     * 
     */
    public function getCols()
    {
        return array_keys($this->cols_fields);
    }

    /**
     * 
     * Returns the table column name for a given entity field name.
     * 
     * @param string $field The entity field name.
     * 
     * @return string The mapped table column name.
     * 
     */
    public function getColForField($field)
    {
        return array_search($field, $this->cols_fields);
    }

    /**
     * 
     * Returns the list of entity fields.
     * 
     * @return array
     * 
     */
    public function getFields()
    {
        return array_values($this->cols_fields);
    }

    /**
     * 
     * Returns the entity field name for a given table column name.
     * 
     * @param string $col The table column name.
     * 
     * @return string The mapped entity field name.
     * 
     */
    public function getFieldForCol($col)
    {
        return $this->cols_fields[$col];
    }

    /**
     * 
     * Returns the identity field name for mapped entities.
     * 
     * @return string The identity field name.
     * 
     */
    public function getIdentityField()
    {
        return $this->identity_field;
    }

    /**
     * 
     * Given an entity object, returns the identity field value.
     * 
     * @param object $entity The entity object.
     * 
     * @return mixed The value of the identity field on the object.
     * 
     */
    public function getIdentityValue($entity)
    {
        $field = $this->identity_field;
        return $entity->$field;
    }

    /**
     * 
     * Returns the primary column name on the table.
     * 
     * @return string The primary column name.
     * 
     */
    public function getPrimaryCol()
    {
        return $this->primary_col;
    }

    /**
     * 
     * Returns the mapped SQL table name.
     * 
     * @return string The mapped SQL table name.
     * 
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * 
     * Returns a column name, dot-prefixed with the table name.
     * 
     * @param string $col The column name.
     * 
     * @return string The fully-qualified table-and-column name.
     * 
     */
    public function getTableCol($col)
    {
        return $this->table . '.' . $col;
    }

    /**
     * 
     * Returns a column name, dot-prefixed with the table name, "AS" its
     * mapped entity name.
     * 
     * @param string $col The column name.
     * 
     * @return string The fully-qualified table-and-column name "AS" the
     * mapped entity name.
     * 
     */
    public function getTableColAsField($col)
    {
        return $this->getTableCol($col) . ' AS ' . $this->getFieldForCol($col);
    }

    /**
     * 
     * Returns the primary column name, dot-prefixed with the table name.
     * 
     * @return string The fully-qualified table-and-primary name.
     * 
     */
    public function getTablePrimaryCol()
    {
        return $this->getTableCol($this->primary_col);
    }

    /**
     * 
     * Returns an array of fully-qualified table columns names "AS" their
     * mapped entity field names.
     * 
     * @param array $cols The column names.
     * 
     * @return array
     * 
     */
    public function getTableColsAsFields($cols)
    {
        $list = [];
        foreach ($cols as $col) {
            $list[] = $this->getTableColAsField($col);
        }
        return $list;
    }

    /**
     * 
     * Given a Select object and an array of column names, modifies the Select
     * SELECT those columns AS their mapped entity field names FROM the mapped
     * table.
     * 
     * @param Select $select The Select object to modify.
     * 
     * @param array $cols The columns to select; if empty, selects all mapped
     * columns.
     * 
     * @return void
     * 
     */
    public function modifySelect(Select $select, array $cols = [])
    {
        if (! $cols) {
            // by default, select all cols
            $cols = $this->getCols();
        }

        $select->from($this->getTable());
        $select->cols($this->getTableColsAsFields($cols));
    }

    /**
     * 
     * Given an Insert query object and an entity object, modifies the Insert 
     * to use the mapped table, with the column names mapped from the entity
     * field names, and binds the entity field values to the query.
     * 
     * @param Insert $insert The Insert query object.
     * 
     * @param object $entity The entity object.
     * 
     * @return void
     * 
     */
    public function modifyInsert(Insert $insert, $entity)
    {
        $data = $this->getInsertData($entity);
        $insert->into($this->table);
        $insert->cols(array_keys($data));
        $insert->addBind($data);
    }

    /**
     * 
     * Given an Update query object and an entity object, modifies the Update
     * to use the mapped table, with the column names mapped from the entity
     * field names, binding the entity field values to the query, and setting
     * a where condition to match the primary column to the identity value.
     * When an array of initial data is present, the update will use only 
     * changed values (instead of sending all the entity values).
     * 
     * @param Update $update The Update query object.
     * 
     * @param object $entity The entity object.
     * 
     * @param array $initial_data The initial data for the entity object; used
     * to determine what values have changed on the entity.
     * 
     * @return void
     * 
     */
    public function modifyUpdate(Update $update, $entity, $initial_data = null)
    {
        $data = $this->getUpdateData($entity, $initial_data);
        $update->table($this->getTable());
        $update->cols(array_keys($data));
        $update->addBind($data);
        $update->where(
            $this->getPrimaryCol() . ' = ?',
            $this->getIdentityValue($entity)
        );
    }

    /**
     * 
     * Given a Delete query object and an entity object, modify the Delete
     * to use the mapped table, and to set a where condition to match the
     * primary column to the identity value.
     * 
     * @param Delete $delete The Delete query object.
     * 
     * @param object $entity The entity object.
     * 
     * @return void
     * 
     */
    public function modifyDelete(Delete $delete, $entity)
    {
        $delete->from($this->table);
        $delete->where(
            $this->getPrimaryCol() . ' = ?',
            $this->getIdentityValue($entity)
        );
    }

    /**
     * 
     * Given an entity object, creates an array of mapped table column names
     * to entity field values for inserts.
     * 
     * @param object $entity The entity object.
     * 
     * @return array
     * 
     */
    public function getInsertData($entity)
    {
        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $data[$col] = $entity->$field;
        }
        return $data;
    }

    /**
     * 
     * Given an entity object, creates an array of mapped table column names
     * to entity field values for updates; when an array of initial data is
     * present, the returned array will have only changed values.
     * 
     * @param object $entity The entity object.
     * 
     * @param array $initial_data The initial data for the entity.
     * 
     * @return array
     * 
     */
    public function getUpdateData($entity, $initial_data = null)
    {
        if ($initial_data) {
            return $this->getUpdateDataChanges($entity, $initial_data);
        }

        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $data[$col] = $entity->$field;
        }
        return $data;
    }

    /**
     * 
     * Given an entity object and an array of initial data, returns an array
     * mapped table columns and changed values.
     * 
     * @param object $entity The entity object.
     * 
     * @param array $initial_data The array of initial data.
     * 
     * @return array
     * 
     */
    public function getUpdateDataChanges($entity, $initial_data)
    {
        $initial_data = (object) $initial_data;
        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $new = $entity->$field;
            $old = $initial_data->$field;
            if (! $this->compare($new, $old)) {
                $data[$col] = $new;
            }
        }
        return $data;
    }
    
    /**
     * 
     * Compares a new value and an old value to see if they are the same.
     * If they are both numeric, use loose (==) equality; otherwise, use
     * strict (===) equality.
     * 
     * @param mixed $new The new value.
     * 
     * @param mixed $old The old value.
     * 
     * @return bool True if they are equal, false if not.
     * 
     */
    public function compare($new, $old)
    {
        $numeric = is_numeric($new) && is_numeric($old);
        if ($numeric) {
            // numeric, compare loosely
            return $new == $old;
        } else {
            // non-numeric, compare strictly
            return $new === $old;
        }
    }
}
