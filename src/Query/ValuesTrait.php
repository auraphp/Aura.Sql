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
namespace Aura\Sql\Query;

/**
 * 
 * A trait for adding column value placeholders and setting values directly.
 * 
 * @package Aura.Sql
 * 
 */
trait ValuesTrait
{
    /**
     * 
     * The column values for the query; the key is the column name and the
     * value is the column value.
     * 
     * @param array
     * 
     */
    protected $values;

    /**
     * 
     * Sets one column value placeholder.
     * 
     * @param string $col The column name.
     * 
     * @return $this
     * 
     */
    public function col($col)
    {
        $key = $this->connection->quoteName($col);
        $this->values[$key] = ":$col";
        return $this;
    }

    /**
     * 
     * Sets multiple column value placeholders.
     * 
     * @param array $cols A list of column names.
     * 
     * @return $this
     * 
     */
    public function cols(array $cols)
    {
        foreach ($cols as $col) {
            $this->col($col);
        }
        return $this;
    }

    /**
     * 
     * Sets a column value directly; the value will not be escaped, although
     * fully-qualified identifiers in the value will be quoted.
     * 
     * @param string $col The column name.
     * 
     * @param string $value The column value expression.
     * 
     * @return $this
     * 
     */
    public function set($col, $value)
    {
        if ($value === null) {
            $value = 'NULL';
        }

        $key = $this->connection->quoteName($col);
        $value = $this->connection->quoteNamesIn($value);
        $this->values[$key] = $value;
        return $this;
    }
}
