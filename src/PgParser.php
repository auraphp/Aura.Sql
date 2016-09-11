<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;

/**
 * Class PgParser
 * Parser specific to PostgreSQL syntax
 * @package Aura\Sql
 */
class PgParser extends BaseParser implements QueryParserInterface
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
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleSingleLineComment($state)
    {
        if ($state->nextCharactersAre('-')) {
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
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleMultiLineComment($state)
    {
        if ($state->nextCharactersAre('*')) {
            // PG handled multiple levels of comments
            $commentLevel = 1;
            while ($commentLevel > 0 && ! $state->done()) {
                $state->copyCurrentCharacter();
                if($state->nextCharactersAre('/*')) {
                    $commentLevel ++;
                }
                elseif($state->nextCharactersAre('*/')) {
                    $commentLevel --;
                }
            }
            $state->copyUntilCharacter('*/');
        }
        else {
            $state->copyCurrentCharacter();
        }
        return $state;
    }

    /**
     *
     * After a signle or double quote string, advance the $current_index to the end of the string
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleQuotedString($state)
    {
        $quoteCharacter = $state->getCurrentCharacter();
        $state->copyCurrentCharacter();
        if (!$state->done()) {
            $state->copyUntilCharacter($quoteCharacter);
        }
        return $state;
    }

    /**
     *
     * After a E or e character, a single quote string use the \ character as an escape character
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
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
                }
                else if ($currentCharacter === "'" && !$escaped) {
                    if($state->nextCharactersAre("'")) {
                        $escaped = true;
                    }
                    else {
                        $inCString = false;
                    }
                }
                if (!$inCString) {
                    // Checking if we have blank characters until next quote. In which case it is the same string
                    $blanks = $state->capture("\\s*'");
                    if($blanks){
                        $state->copyUntilCharacter("'");
                        $state->copyCurrentCharacter();
                        $inCString = true;
                    }
                }
            } while(!$state->done() && $inCString);
        }
        return $state;
    }

    /**
     *
     * $ charaters can be used to create strings
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleDollar($state)
    {
        $identifier =  $state->capture('\\$([a-zA-Z_]\\w*)*\\$');
        if ($identifier) {
            $state->copyUntilCharacter($identifier);
            $state->copyUntilCharacter($identifier);
        }
        return $state;
    }

    /**
     *
     * As the : character can appear in array accessors, we have to manage this state
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleArray($state)
    {
        $state->copyUntilCharacter(']');
        return $state;
    }

    /**
     *
     * Check if a ':' colon character is followed by what can be a named placeholder.
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleColon($state)
    {
        $colon_number = 0;
        do {
            $state->copyCurrentCharacter();
            $colon_number++;
        }
        while($state->getCurrentCharacter() === ':');

        if ($colon_number != 1) {
            return $state;
        }

        $name = $state->getIdentifier();

        if (! $name) {
            return $state;
        }

        $value = $state->getNamedParameterValue($name);
        $placeholder_identifiers = '';

        if (! is_array($value)) {
            $identifier = $state->storeValueToBind($name, $value);
            $placeholder_identifiers .= $identifier;
        }
        else {
            foreach ($value as $sub) {
                $identifier = $state->storeValueToBind($name, $sub);
                if ($placeholder_identifiers) {
                    $placeholder_identifiers .= ', :';
                }
                $placeholder_identifiers .= $identifier;
            }
        }
        $state->passString($name);
        $state->addStringToStatement($placeholder_identifiers);
        return $state;
    }

    /**
     *
     * Replace a numbered placeholder character by multiple ones if a numbered placeholder contains an array.
     * As the '?' character can't be used with PG queries, replace it with a named placeholder
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleNumberedParameter($state)
    {
        $value = $state->getFirstUnusedNumberedValue();

        $name = '__numbered';

        if (! is_array($value)) {
            $placeholder_identifiers = ':' . $state->storeValueToBind($name, $value);
        }
        else {
            $placeholder_identifiers = '';
            foreach ($value as $sub) {
                $identifier = ':' . $state->storeValueToBind($name, $sub);
                if ($placeholder_identifiers) {
                    $placeholder_identifiers .= ', ';
                }
                $placeholder_identifiers .= $identifier;
            }
        }
        $state->passString($this->getNumberedPlaceholderCharacter());
        $state->addStringToStatement($placeholder_identifiers);

        return $state;
    }

    /**
     * Saves the fact a new statement is starting
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleSemiColon($state)
    {
        $uselessCharacters = $state->capture(';\\s*');
        $state->passString($uselessCharacters);
        $state->setNewStatementCharacterFound(true);
        return $state;
    }
}