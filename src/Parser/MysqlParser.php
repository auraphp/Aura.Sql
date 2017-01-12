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
class MysqlParser extends AbstractParser implements ParserInterface
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
     * If a '-' is followed by another one and a space, it is a valid single line comment
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleSingleLineComment($state)
    {
        if ($state->nextCharactersAre('- ')) {
            // One line comment
            $state->copyUntilCharacter("\n");
        }
        else {
            $state->copyCurrentCharacter();
        }

        return $state;
    }

    /**
     *
     * If the character following a '/' one is a '*', advance the $current_index to the end of this multiple line comment
     * MySQL does not handle multiple levels of comments
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleMultiLineComment($state)
    {
        if ($state->nextCharactersAre('*')) {
            $state->copyUntilCharacter('*/');
        }
        else {
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
        while (!$state->done()) {
            $currentCharacter = $state->getCurrentCharacter();
            if ($currentCharacter === '\\') {
                $backslashEscaping = !$backslashEscaping;
            }
            else if($currentCharacter === $quoteCharacter && !$backslashEscaping) {
                $state->copyCurrentCharacter();
                if( !$state->nextCharactersAre($quoteCharacter)) {
                    return $state;
                }
            }
            else {
                $backslashEscaping = false;
            }
            $state->copyCurrentCharacter();
        }
        return $state;
    }
}
