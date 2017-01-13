<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql\Parser;

use Aura\Sql\Query;
use Aura\Sql\Exception;

/**
 *
 * Base class for parsing/rebuilding functionality.
 *
 * @package Aura\Sql
 *
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     *
     * List of handlers to call when a character is found.
     * The key is the character, the value is a callable which takes a State as parameter and returns a State
     *
     * @var array
     */
    protected $statementPartsHandlers = array();

    /**
     * @var string $numberedPlaceHolderCharacter Character used to define numbered placeholders. Default is "?"
     */
    protected $numberedPlaceHolderCharacter = "?";

    public function rebuild($query)
    {
        $queries = array();
        $charset = 'UTF-8';
        /** @var State $state */
        $state = new State($query->getString(), $query->getParameters(), $charset);

        $last_check_index = -1;

        while (! $state->done()) {
            if ($state->getCurrentIndex() <= $last_check_index) {
                throw new Exception\ParserLoop(
                    'SQL rebuilder seems to be in an infinite loop.'
                );
            }
            $last_check_index = $state->getCurrentIndex();

            if (isset($this->statementPartsHandlers[$state->getCurrentCharacter()])) {
                $handler = $this->statementPartsHandlers[$state->getCurrentCharacter()];
                $state = call_user_func($handler, $state);
                // if we encountered a statement separator, we have to prepare a new Query
                if ($state->isNewStatementCharacterFound()) {
                    $this->storeQuery($state, $queries);
                    $state->resetFinalStatement();
                }
            } else {
                $state->copyCurrentCharacter();
            }
        }
        $this->storeQuery($state, $queries);
        return $queries;
    }

    /**
     *
     * Add a Query using the current statement and values from a State
     *
     * @param State $state
     * @param Query[] $queries reference to the array holding a list of queries
     *
     */
    private function storeQuery($state, &$queries)
    {
        $statement = $state->getFinalStatement();
        if (! $this->isStatementEmpty($statement)) {
            $queries[] = new Query($statement, $state->getValuesToBind());
        }
    }

    /**
     *
     * Returns if an SQL statement is empty
     *
     * @param string $statement
     *
     * @return bool
     */
    private function isStatementEmpty($statement)
    {
        return mb_ereg_match('^\\s*$', $statement);
    }

    /**
     * Common handling methods.
     */

    /**
     *
     * After a single or double quote string, advance the $current_index to the end of the string
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleQuotedString($state)
    {
        $quoteCharacter = $state->getCurrentCharacter();
        $state->copyCurrentCharacter();
        if (! $state->done()) {
            $state->copyUntilCharacter($quoteCharacter);
        }
        return $state;
    }

    /**
     *
     * Check if a ':' colon character is followed by what can be a named placeholder.
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleColon($state)
    {
        $colon_number = 0;
        do {
            $state->copyCurrentCharacter();
            $colon_number++;
        } while ($state->getCurrentCharacter() === ':');

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
        } else {
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
     * @param State $state
     *
     * @return State
     */
    protected function handleNumberedParameter($state)
    {
        $value = $state->getFirstUnusedNumberedValue();

        $name = '__numbered';

        if (! is_array($value)) {
            $placeholder_identifiers = ':' . $state->storeValueToBind($name, $value);
        } else {
            $placeholder_identifiers = '';
            foreach ($value as $sub) {
                $identifier = ':' . $state->storeValueToBind($name, $sub);
                if ($placeholder_identifiers) {
                    $placeholder_identifiers .= ', ';
                }
                $placeholder_identifiers .= $identifier;
            }
        }
        $state->passString($this->numberedPlaceHolderCharacter);
        $state->addStringToStatement($placeholder_identifiers);

        return $state;
    }

    /**
     * Saves the fact a new statement is starting
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleSemiColon($state)
    {
        $uselessCharacters = $state->capture(';\\s*');
        $state->passString($uselessCharacters);
        $state->setNewStatementCharacterFound(true);
        return $state;
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
            $state->copyUntilCharacter("\n");
        } else {
            $state->copyCurrentCharacter();
        }

        return $state;
    }

    /**
     *
     * If the character following a '/' one is a '*', advance the $current_index to the end of this multiple line comment.     *
     * @param State $state
     *
     * @return State
     */
    protected function handleMultiLineComment($state)
    {
        if ($state->nextCharactersAre('*')) {
            $state->copyUntilCharacter('*/');
        } else {
            $state->copyCurrentCharacter();
        }
        return $state;
    }
}
