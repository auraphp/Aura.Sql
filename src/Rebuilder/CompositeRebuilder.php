<?php
/**
 * This file is part of Aura for PHP.
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace Aura\Sql\Rebuilder;

/**
 * Do several rebuild steps on the query
 * @package aura/sql
 */
class CompositeRebuilder implements RebuilderInterface
{
    /** @var RebuilderInterface[] */
    private $rebuilder = [];

    /**
     * @param RebuilderInterface $rebuilder
     */
    public function add(RebuilderInterface $rebuilder)
    {
        $this->rebuilder[] = $rebuilder;
    }

    /**
     * Rebuilds a query and its parameters to adapt it to PDO's limitations,
     * and returns a list of queries.
     * @param \Aura\Sql\Rebuilder\Query $query
     * @return \Aura\Sql\Rebuilder\Query the rebuilt query
     */
    public function rebuild(Query $query) : Query
    {
        foreach ($this->rebuilder as $rebuilder) {
            $query = $rebuilder->rebuild($query);
        }
        return $query;
    }
}
