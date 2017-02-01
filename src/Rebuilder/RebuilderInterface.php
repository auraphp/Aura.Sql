<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql\Rebuilder;

/**
 * Interface for query rebuilding functionality.
 * @package aura/sql
 */
interface RebuilderInterface
{
    /**
     * @param \Aura\Sql\Rebuilder\Query $query
     * @return \Aura\Sql\Rebuilder\Query the rebuilt query
     */
    public function rebuild(Query $query);
}
