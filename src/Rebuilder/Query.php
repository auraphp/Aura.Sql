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
     * @var string An sql query statement string
     */
    private $statement = '';

    /**
     * @var array values associated with a query
     */
    private $values = [];

    /**
     * Query constructor.
     * @param string $sql
     * @param array  $values
     */
    public function __construct($sql, $values = [])
    {
        $this->statement = $sql;
        $this->values    = $values;
    }

    /**
     * Returns the SQL statement
     * @return string
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Returns the sql query parameter values
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}
