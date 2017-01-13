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
 * Query parser for Microsoft SQL Server flavored queries
 * @package aura/sql
 */
class SqlsrvParser extends AbstractParser
{
    /**
     * Constructor. Sets up the array of callbacks.
     */
    public function __construct()
    {
        $this->handlers = array(
            '-' => 'handleSingleLineComment',
            '/' => 'handleMultiLineComment',
            '"' => 'handleQuotedString',
            "'" => 'handleQuotedString',
            "[" => 'handleIdentifier',
            ':' => 'handleColon',
            '?' => 'handleNumberedParameter',
            ';' => 'handleSemiColon',
        );
    }

    /**
     *
     * Handles `[table.col]` (etc.) identifiers.
     *
     * @param State $state The current parser state.
     *
     */
    protected function handleIdentifier($state)
    {
        $state->copyUntilCharacter(']');
    }
}
