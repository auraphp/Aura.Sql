<?php
/**
 * This file is part of Aura for PHP.
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace Aura\Sql\Rebuilder;

/**
 * A rebuilt query statement, with the values to bind to it.
 * @package aura/sql
 */
class Query
{
    /**
     * @var \string An sql query statement string
     */
    private $statement = '';

    /**
     * @var array values provided by user may include extra values eg a row from the database with some unneeded
     */
    private $all_values = [];

    /**
     * @var array values actually used by the query
     */
    private $used_values = [];

    /**
     * Query constructor.
     * @param \string $sql
     * @param array   $all_values
     * @param array   $used_values
     */
    public function __construct($sql, $all_values = [], $used_values = [])
    {
        $this->statement   = $sql;
        $this->all_values  = $all_values;
        $this->used_values = $used_values;
    }

    /**
     * Returns the SQL statement
     * @return \string
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Returns the sql query parameter values
     * @return array
     */
    public function getAllValues()
    {
        return $this->all_values;
    }

    /**
     * Returns the sql query parameter values
     * @return array
     */
    public function getUsedValues()
    {
        return $this->used_values;
    }

    /**
     * @param string $name
     */
    public function useValue($name)
    {
        $this->used_values[$name] = $this->all_values[$name];
    }
}
