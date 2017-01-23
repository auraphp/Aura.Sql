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
 * @package aura/sql
 *
 */
class MysqlParser extends AbstractParser
{
    protected $handlers = [
        '-' => 'handleSingleLineComment',
        '/' => 'handleMultiLineComment',
        '"' => 'handleMySQLQuotedString',
        "'" => 'handleMySQLQuotedString',
        "`" => 'handleQuotedString',
        ':' => 'handleColon',
        '?' => 'handleNumberedParameter',
        ';' => 'handleSemiColon',
    ];

    /**
     *
     * If a '-' is followed by another one and any whitespace, it is a valid
     * single line comment. This differs from the standard, where no trailing
     * whitespace is required.
     *
     * @param State $state The parser state.
     *
     */
    protected function handleSingleLineComment($state)
    {
        $isComment = $state->nextCharactersAre("- ")
            || $state->nextCharactersAre("-\t")
            || $state->nextCharactersAre("-\n")
            || $state->nextCharactersAre("-\r");

        if ($isComment) {
            $state->copyUntilCharacter("\n");
        } else {
            $state->copyCurrentCharacter();
        }
    }

    /**
     *
     * By default MySQL can use \ or a doubling of a quote to escape it in a string literal
     *
     * @param State $state The parser state.
     *
     */
    protected function handleMySQLQuotedString($state)
    {
        $quoteCharacter = $state->getCurrentCharacter();
        $state->copyCurrentCharacter();
        $backslashEscaping = false;
        while (! $state->done()) {
            $currentCharacter = $state->getCurrentCharacter();
            if ($currentCharacter === '\\') {
                $backslashEscaping = ! $backslashEscaping;
            } elseif ($currentCharacter === $quoteCharacter && ! $backslashEscaping) {
                $state->copyCurrentCharacter();
                if ( ! $state->nextCharactersAre($quoteCharacter)) {
                    return;
                }
            } else {
                $backslashEscaping = false;
            }
            $state->copyCurrentCharacter();
        }
    }
}
