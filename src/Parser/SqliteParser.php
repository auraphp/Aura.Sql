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
 * Query parser for Sqlite flavored queries
 * @package Aura\Sql
 */
class SqliteParser extends AbstractParser
{
    /**
     * Constructor. Sets up the array of callbacks.
     */
    public function __construct()
    {
        $this->statementPartsHandlers = array(
            '-' => array($this, 'handleSingleLineComment'),
            '/' => array($this, 'handleMultiLineComment'),
            '"' => array($this, 'handleQuotedString'),
            "'" => array($this, 'handleSqliteQuotedString'),
            ':' => array($this, 'handleColon'),
            '?' => array($this, 'handleNumberedParameter'),
            ';' => array($this, 'handleSemiColon'),
        );
    }

    /**
     *
     * Sqlite can use a doubling of a quote to escape it in a string literal
     *
     * @param State $state
     *
     * @return State
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
                    return $state;
                }
            }
            $state->copyCurrentCharacter();
        }
        return $state;
    }
}
