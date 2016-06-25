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
 * Class PgParser
 * Parser specific to PostgreSQL syntax
 * @package Aura\Sql
 */
class PgParser extends BaseParser implements QueryParserInterface
{
    public function normalize($query)
    {
        return array($query);
    }
}