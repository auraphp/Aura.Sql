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
 * Parsing/rebuilding functionality for the sqlite driver.
 *
 * @package aura/sql
 *
 */
class SqliteParser extends AbstractParser
{
    protected $handlers = [
        '-' => 'handleSingleLineComment',
        '/' => 'handleMultiLineComment',
        '"' => 'handleQuotedString',
        "'" => 'handleSqliteQuotedString',
        ':' => 'handleColon',
        '?' => 'handleNumberedParameter',
        ';' => 'handleSemiColon',
    ];

    /**
     *
     * Sqlite can use a doubling of a quote to escape it in a string literal
     *
     * @param State $state The parser state.
     *
     */
    protected function handleSqliteQuotedString($state)
    {
        $quoteCharacter = $state->getCurrentCharacter();
        $state->copyCurrentCharacter();
        while (! $state->done()) {
            $currentCharacter = $state->getCurrentCharacter();
            if ($currentCharacter === $quoteCharacter) {
                $state->copyCurrentCharacter();
                if ( ! $state->nextCharactersAre($quoteCharacter)) {
                    return;
                }
            }
            $state->copyCurrentCharacter();
        }
    }
}
