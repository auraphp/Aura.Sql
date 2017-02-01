<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql\Parser;

use Aura\Sql\Rebuilder\Query;

/**
 *
 * Interface for query parsing/rebuilding functionality.
 *
 * @package aura/sql
 *
 */
interface ParserInterface
{
    /**
     *
     * Rebuilds a query and its parameters to adapt it to PDO's limitations,
     * and returns a list of queries.
     *
     * @param string $string The query statement string.
     *
     * @param array $parameters Bind these values into the query.
     *
     * @return Query[]
     *
     */
    public function rebuild($string, array $parameters = []);
}
