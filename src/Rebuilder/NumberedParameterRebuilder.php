<?php
/**
 * This file is part of Aura for PHP.
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace Aura\Sql\Rebuilder;

use Aura\Sql\Parser\ParserInterface;

/**
 * Replace ? parameters with named parameters
 * @package aura/sql
 */
class NumberedParameterRebuilder implements RebuilderInterface
{
    /** @var ParserInterface */
    private $parser;

    /**
     * @param \Aura\Sql\Parser\ParserInterface $parser
     */
    public function __construct(\Aura\Sql\Parser\ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param \Aura\Sql\Rebuilder\Query $query
     * @return \Aura\Sql\Rebuilder\Query the rebuilt query
     */
    public function rebuild(Query $query)
    {
        if (strpos($query->getStatement(), '?') === false) {
            // there are no numbered parameters, so we don't need to parse the SQL for them
            return $query;
        }
        $result = $this->parser->rebuild($query->getStatement(), $query->getAllValues());
        return new Query($result[0]->getStatement(), $query->getAllValues(), $result[0]->getUsedValues());
    }
}
