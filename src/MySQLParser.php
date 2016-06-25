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
 * Class MySQLParser
 * Query parser for MySQL flavored queries
 * @package Aura\Sql
 */
class MySQLParser implements QueryParserInterface
{
    /**
     * @inheritdoc
     */
    public function normalize($query)
    {
        return array($query);
    }
}