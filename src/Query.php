<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;

/**
 * Class Query
 * Contains a SQL query string and the parameters to bind
 * @package Aura\Sql
 */
class Query
{
    /**
     * @var string A query string
     */
    private $string = '';

    /**
     * @var array Parameters associated with a query
     */
    private $parameters = array();

    /**
     * Query constructor.
     * @param string $string
     * @param array $parameters
     */
    public function __construct($string, $parameters = array())
    {
        $this->string = $string;
        $this->parameters = $parameters;
    }

    /**
     * Returns a Query SQL string
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * Returns a Query parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
