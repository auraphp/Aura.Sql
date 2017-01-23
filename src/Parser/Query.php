<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql\Parser;

/**
 *
 * A rebuilt query statement, with the values to bind to it.
 *
 * @package aura/sql
 *
 */
class Query
{
    /**
     * @var string A query statement
     */
    private $statement = '';

    /**
     * @var array values associated with a query
     */
    private $values = array();

    /**
     * Query constructor.
     * @param string $statement
     * @param array $values
     */
    public function __construct($statement, $values = array())
    {
        $this->statement = $statement;
        $this->values = $values;
    }

    /**
     * Returns a Query SQL statement
     * @return statement
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Returns a Query values
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}
