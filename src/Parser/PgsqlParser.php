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
 * Class PgsqlParser
 * Parser specific to PostgreSQL syntax
 * @package Aura\Sql
 */
class PgsqlParser extends AbstractParser implements ParserInterface
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
            "'" => array($this, 'handleQuotedString'),
            'E' => array($this, 'handlePossibleCStyleString'),
            'e' => array($this, 'handlePossibleCStyleString'),
            ':' => array($this, 'handleColon'),
            '?' => array($this, 'handleNumberedParameter'),
            ';' => array($this, 'handleSemiColon'),
            '$' => array($this, 'handleDollar'),
            '[' => array($this, 'handleArray'),
        );
    }

    /**
     *
     * Returns a modified statement, values and current index depending on what follow a '-' character.
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleSingleLineComment($state)
    {
        if ($state->nextCharactersAre('-')) {
            // One line comment
            $state->copyUntilCharacter("\n");
        } else {
            $state->copyCurrentCharacter();
        }

        return $state;
    }

    /**
     *
     * If the character following a '/' one is a '*', advance the $current_index to the end of this multiple line comment
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleMultiLineComment($state)
    {
        if ($state->nextCharactersAre('*')) {
            // PG handled multiple levels of comments
            $commentLevel = 1;
            while ($commentLevel > 0 && ! $state->done()) {
                $state->copyCurrentCharacter();
                if ($state->nextCharactersAre('/*')) {
                    $commentLevel ++;
                } elseif ($state->nextCharactersAre('*/')) {
                    $commentLevel --;
                }
            }
            $state->copyUntilCharacter('*/');
        } else {
            $state->copyCurrentCharacter();
        }
        return $state;
    }

    /**
     *
     * After a E or e character, a single quote string use the \ character as an escape character
     *
     * @param State $state
     *
     * @return State
     */
    protected function handlePossibleCStyleString($state)
    {
        $state->copyCurrentCharacter();
        if (!$state->done() && ($currentCharacter = $state->getCurrentCharacter()) === "'") {
            $escaped = false;
            $inCString = true;
            do {
                $state->copyCurrentCharacter();
                $currentCharacter = $state->getCurrentCharacter();
                if ($currentCharacter === '\\') {
                    $escaped = !$escaped;
                } elseif ($currentCharacter === "'" && !$escaped) {
                    if ($state->nextCharactersAre("'")) {
                        $escaped = true;
                    } else {
                        $inCString = false;
                    }
                }
                if (!$inCString) {
                    // Checking if we have blank characters until next quote. In which case it is the same string
                    $blanks = $state->capture("\\s*'");
                    if ($blanks) {
                        $state->copyUntilCharacter("'");
                        $state->copyCurrentCharacter();
                        $inCString = true;
                    }
                }
            } while (!$state->done() && $inCString);
        }
        return $state;
    }

    /**
     *
     * $ charaters can be used to create strings
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleDollar($state)
    {
        $identifier =  $state->capture('\\$([a-zA-Z_]\\w*)*\\$');
        if ($identifier) {
            // Copy until the end of the starting tag
            $state->copyUntilCharacter($identifier);
            // Copy everything between the start and end tag (included)
            $state->copyUntilCharacter($identifier);
        }
        return $state;
    }

    /**
     *
     * As the : character can appear in array accessors, we have to manage this state
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleArray($state)
    {
        $state->copyUntilCharacter(']');
        return $state;
    }
}
