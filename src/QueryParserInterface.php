<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;


interface QueryParserInterface
{
    /**
     * Normalize a query and its parameters to adapt it to PDO's limitations and returns a list of queries
     * @param Query $query
     * @return Query[]
     */
    public function normalize($query);
}