<?php
/**
 * This file is part of Aura for PHP.
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace Aura\Sql\Rebuilder;

/**
 * do nothing
 * @package aura/sql
 */
class NullRebuilder implements RebuilderInterface
{
    /**
     * @param \Aura\Sql\Rebuilder\Query $query
     * @return \Aura\Sql\Rebuilder\Query the rebuilt query
     */
    public function rebuild(Query $query)
    {
        return $query;
    }
}
