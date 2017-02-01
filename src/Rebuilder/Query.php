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
    private $sql = '';

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
        $this->sql    = $sql;
        $this->values = $values;
    }

    /**
     * Returns a the updated SQL statement
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Returns the query parameter values
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}
