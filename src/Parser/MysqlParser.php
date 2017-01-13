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
 * Class MysqlParser
 * Query parser for MySQL flavored queries
 * @package Aura\Sql
 */
class MysqlParser extends AbstractParser
{
    /**
     * Constructor. Sets up the array of callbacks.
     */
    public function __construct()
    {
        $this->statementPartsHandlers = array(
            '-' => array($this, 'handleSingleLineComment'),
            '/' => array($this, 'handleMultiLineComment'),
            '"' => array($this, 'handleMySQLQuotedString'),
            "'" => array($this, 'handleMySQLQuotedString'),
            "`" => array($this, 'handleQuotedString'),
            ':' => array($this, 'handleColon'),
            '?' => array($this, 'handleNumberedParameter'),
            ';' => array($this, 'handleSemiColon'),
        );
    }

    /**
     *
     * If a '-' is followed by another one and any whitespace, it is a valid
     * single line comment. This differs from the standard, where no trailing
     * whitespace is required.
     *
     * @param State $state
     *
     * @return State
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

        return $state;
    }

    /**
     *
     * By default MySQL can use \ or a doubling of a quote to escape it in a string literal
     *
     * @param State $state
     *
     * @return State
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
                    return $state;
                }
            } else {
                $backslashEscaping = false;
            }
            $state->copyCurrentCharacter();
        }
        return $state;
    }
}
