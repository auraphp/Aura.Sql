<?php
namespace Aura\Sql;

// right now we have to extend this to make it useful.
// make it so that it's constructed instead?
// 
// we have data *objects* for this but they can be any kind of object, as
// long as the properties are accessible *as properties* (e.g. via __get()).

use Aura\Sql\Query\Delete;
use Aura\Sql\Query\Insert;
use Aura\Sql\Query\Select;
use Aura\Sql\Query\Update;

abstract class AbstractMapper
{
    protected $table;
    
    protected $cols_fields = [];
    
    protected $primary_col;
    
    protected $identity_field;
    
    public function getCols()
    {
        return array_keys($this->cols_fields);
    }
    
    public function getColForField($field)
    {
        return array_search($field, $this->cols_fields);
    }
    
    public function getFields()
    {
        return array_values($this->cols_fields);
    }
    
    public function getFieldForCol($col)
    {
        return $this->cols_fields[$col];
    }
    
    public function getIdentityField()
    {
        return $this->identity_field;
    }
    
    public function getIdentityValue($object)
    {
        $field = $this->identity_field;
        return $object->$field;
    }
    
    public function getPrimaryCol()
    {
        return $this->primary_col;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function getTableCol($col)
    {
        return $this->table . '.' . $col;
    }
    
    public function getTableColAsField($col)
    {
        return $this->getTableCol($col) . ' AS ' . $this->getFieldForCol($col);
    }
    
    public function getTablePrimaryCol()
    {
        return $this->getTableCol($this->primary_col);
    }
    
    public function getTableColsAsFields($cols)
    {
        $list = [];
        foreach ($cols as $col) {
            $list[] = $this->getTableColAsField($col);
        }
        return $list;
    }
    
    public function modifySelect(Select $select, array $cols = [])
    {
        if (! $cols) {
            // by default, select all cols
            $cols = $this->getCols();
        }
        
        $select->from($this->getTable());
        $select->cols($this->getTableColsAsFields($cols));
    }
    
    public function modifyInsert(Insert $insert, $object)
    {
        $data = $this->getInsertData($object);
        $insert->into($this->table);
        $insert->cols(array_keys($data));
        $insert->addBind($data);
    }
    
    public function modifyUpdate(Update $update, $object, $initial_data = null)
    {
        $data = $this->getUpdateData($object, $initial_data);
        $update->table($this->getTable());
        $update->cols(array_keys($data));
        $update->addBind($data);
        $update->where(
            $this->getPrimaryCol() . ' = ?',
            $this->getIdentityValue($object)
        );
    }
    
    public function modifyDelete(Delete $delete, $object)
    {
        $delete->from($this->table);
        $delete->where(
            $this->getPrimaryCol() . ' = ?',
            $this->getIdentityValue($object)
        );
    }
    
    public function getInsertData($object)
    {
        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $data[$col] = $object->$field;
        }
        return $data;
    }

    public function getUpdateData($object, $initial_data = null)
    {
        if ($initial_data) {
            return $this->getUpdateDataChanges($object, $initial_data);
        }

        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $data[$col] = $object->$field;
        }
        return $data;
    }

    public function getUpdateDataChanges($object, $initial_data)
    {
        $initial_data = (object) $initial_data;
        $data = [];
        foreach ($this->cols_fields as $col => $field) {
            $new = $object->$field;
            $old = $initial_data->$field;
            if (! $this->compare($new, $old)) {
                $data[$col] = $new;
            }
        }
        return $data;
    }
    
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
