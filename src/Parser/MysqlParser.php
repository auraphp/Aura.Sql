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
 * Parsing/rebuilding functionality for the mysql driver.
 *
 * @package Aura.Sql
 *
 */
class MysqlParser extends AbstractParser
{
    protected $split = [
        // single-quoted string
        "'(?:[^'\\\\]|\\\\'?)*'",
        // double-quoted string
        '"(?:[^"\\\\]|\\\\"?)*"',
        // backtick-quoted string
        '`(?:[^`\\\\]|\\\\`?)*`',
    ];

    protected $skip = '/^(\'|\"|\`)/um';
}
