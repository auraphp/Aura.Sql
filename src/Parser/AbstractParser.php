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
 * Parsing/rebuilding functionality for all drivers.
 *
 * @package aura/sql
 *
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     *
     * List of handlers to call when a character is found.
     *
     * The key is the character, the value is a method name which takes a State
     * as parameter and returns a State.
     *
     * @var array
     *
     */
    protected $handlers = array();

    /**
     *
     * Character used to define numbered placeholders.
     *
     * @var string
     *
     */
    protected $numberedPlaceHolderCharacter = "?";

    /**
     *
     * Given a query string and parameters, rebuilds it so that parameters all
     * match up, and replaces array-based placeholders.
     *
     * @param string $string The query statement string.
     *
     * @param array $parameters Bind these values into the query.
     *
     * @return Query[]
     *
     */
    public function rebuild($string, array $parameters = [])
    {
        $query = new Query($string, $parameters);
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

            if (isset($this->handlers[$state->getCurrentCharacter()])) {
                $handler = $this->handlers[$state->getCurrentCharacter()];
                $this->$handler($state);
                // if we encountered a statement separator,
                // we have to prepare a new Query
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
     * Add a Query using the current statement and values from a State.
     *
     * @param State $state The parser state.
     *
     * @param Query[] $queries reference to the array holding a list of queries.
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
     * Is an SQL statement is empty?
     *
     * @param string $statement The SQL statement.
     *
     * @return bool True if empty, false if not.
     *
     */
    private function isStatementEmpty($statement)
    {
        return trim($statement) === '';
    }

    /**
     *
     * After a single or double quote string, advance the $current_index to the end of the string
     *
     * @param State $state The parser state.
     *
     */
    protected function handleQuotedString($state)
    {
        $quoteCharacter = $state->getCurrentCharacter();
        $state->copyCurrentCharacter();
        if (! $state->done()) {
            $state->copyUntilCharacter($quoteCharacter);
        }
    }

    /**
     *
     * Check if a ':' colon character is followed by what can be a named placeholder.
     *
     * @param State $state The parser state.
     *
     */
    protected function handleColon($state)
    {
        $colon_number = 0;
        do {
            $state->copyCurrentCharacter();
            $colon_number++;
        } while ($state->getCurrentCharacter() === ':');

        if ($colon_number != 1) {
            return;
        }

        $name = $state->getIdentifier();
        if (! $name) {
            return;
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
    }

    /**
     *
     * Replace a numbered placeholder character by multiple ones if a numbered placeholder contains an array.
     * As the '?' character can't be used with PG queries, replace it with a named placeholder
     *
     * @param State $state The parser state.
     *
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
    }

    /**
     *
     * Saves the fact a new statement is starting.
     *
     * @param State $state The parser state.
     *
     */
    protected function handleSemiColon($state)
    {
        while (! $state->done())
        {
            $character = $state->getCurrentCharacter();
            if (! in_array($character, array(';', "\r", "\n", "\t", " "), true))
            {
                break;
            }
            $state->passString($character);
        }
        $state->setNewStatementCharacterFound(true);
    }

    /**
     *
     * Returns a modified statement, values, and current index depending on what
     * follow a '-' character.
     *
     * @param State $state The parser state.
     *
     */
    protected function handleSingleLineComment($state)
    {
        if ($state->nextCharactersAre('-')) {
            $state->copyUntilCharacter("\n");
        } else {
            $state->copyCurrentCharacter();
        }
    }

    /**
     *
     * If the character following a '/' one is a '*', advance the
     * $current_index to the end of this multiple line comment.
     *
     * @param State $state The parser state.
     *
     */
    protected function handleMultiLineComment($state)
    {
        if ($state->nextCharactersAre('*')) {
            $state->copyUntilCharacter('*/');
        } else {
            $state->copyCurrentCharacter();
        }
    }
}
