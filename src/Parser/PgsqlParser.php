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
 * Parsing/rebuilding functionality for the pgsl driver.
 *
 * @package Aura.Sql
 *
 */
class PgsqlParser extends AbstractParser
{
    protected $split = [
        // single-quoted string
        "'(?:[^'\\\\]|\\\\'?)*'",
        // double-quoted string
        '"(?:[^"\\\\]|\\\\"?)*"',
        // double-dollar string (empty dollar-tag)
        '\$\$(?:[^\$]?)*\$\$',
        // dollar-tag string -- DOES NOT match tags properly
        '\$[^\$]+\$.*\$[^\$]+\$',
    ];

    protected $skip = '/^(\'|\"|\$|\:[^a-zA-Z_])/um';
}
