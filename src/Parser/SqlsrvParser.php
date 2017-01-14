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
 * Parsing/rebuilding functionality for the sqlsrv driver.
 *
 * @package aura/sql
 *
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
     * @param State $state The parser state.
     *
     */
    protected function handleIdentifier($state)
    {
        $state->copyUntilCharacter(']');
    }
}
