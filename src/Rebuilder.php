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
 *
 * This support class for ExtendedPdo rebuilds an SQL statement for automatic
 * binding of values.
 *
 * @package Aura.Sql
 *
 */
class Rebuilder implements RebuilderInterface
{
    /**
     *
     * List of handlers to call when a character is found.
     * The key is the character, the value is a callable which takes a RebuildState as parameter and returns a RebuilderState
     *
     * @var array
     */
    protected $statementPartsHandlers = array();

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
            ':' => array($this, 'handleColon'),
            '?' => array($this, 'handleQuestionMark'),
        );
    }

    /**
     *
     * Registers a callable to use when a character is found in the statement
     *
     * @param string $key Character to check for
     * @param callable $callback A callback which has the same properties as those found in $statementPartsHandlers
     */
    public function registerStatementPartsHandler($key, $callback)
    {
        $this->statementPartsHandlers[$key] = $callback;
    }

    /**
     *
     * Rebuilds a statement with array values put in individual values and parameters. Multiple occurrences of the same
     * placeholder are also replaced.
     *
     * @param string $statement The statement to rebuild.
     *
     * @param array $values The values to bind and/or replace into a statement.
     *
     * @param string $charset The character set the statement is using.
     *
     * @return array An array where element 0 is the rebuilt statement and
     * element 1 is the rebuilt array of values.
     *
     * @throws \Exception
     *
     */
    public function rebuildStatement($statement, $values = array(), $charset = 'UTF-8')
    {
        $state = new RebuilderState($statement, $values, $charset);

        $last_check_index = -1;

        while (! $state->done()) {
            if ($state->getCurrentIndex() <= $last_check_index) {
                throw (new \Exception('SQL rebuilder seems to be in an infinite loop.'));
            }
            $last_check_index = $state->getCurrentIndex();

            if (isset($this->statementPartsHandlers[$state->getCurrentCharacter()])) {
                $handler = $this->statementPartsHandlers[$state->getCurrentCharacter()];
                call_user_func($handler, $state);
            }
            else {
                $state->copyCurrentCharacter();
            }
        }

        return array($state->getFinalStatement(), $state->getValuesToBind());
    }

    /**
     *
     * Returns the length of a quoted string part of statement
     *
     * @param string $statement The SQL statement
     *
     * @param int $position Position of the quote character starting the string
     *
     * @param string $quote_character Quote character, ' or "
     *
     * @param string $charset Charset of the string
     *
     * @return int
     */
    protected function getQuotedStringLength($statement, $position, $quote_character, $charset)
    {
        $start = $position;
        $last_index = mb_strlen($statement, $charset) - 1;
        // Search for end of string character, passing '', "", \' and \" groupings.
        $end_found = false;
        $length = 0;
        while (!$end_found)
        {
            $end_of_quoted_string_index = mb_strpos($statement, $quote_character, $position + 1, $charset);
            if ($end_of_quoted_string_index === false) {
                $length = $last_index - $start;
                break;
            }

            // Count the number of \ characters before the quote character found.
            $escape_count = 0;
            while (mb_substr($statement, $end_of_quoted_string_index - ($escape_count + 1), 1, $charset) === "\\")
            {
                $escape_count ++;
            }
            // If it is even, count the number of same character after the current one
            if ($escape_count % 2 == 0)
            {
                $escape_count = 0;
                while (mb_substr($statement, $end_of_quoted_string_index + $escape_count + 1, 1, $charset) === $quote_character)
                {
                    $escape_count ++;
                }
                $position = $end_of_quoted_string_index + $escape_count;
                if ($escape_count % 2 == 0)
                {
                    $length = $end_of_quoted_string_index + $escape_count - $start + 1;
                    $end_found = true;
                }
            }
            else {
                // Character is escaped so we try to find another one after
                $position = $end_of_quoted_string_index;
            }
        }
        return $length;
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
            $state->copyUntilCharacter('*/');
        }
        else {
            $state->copyCurrentCharacter();
        }
        return $state;
    }

    /**
     *
     * After a single quote or double quote string, advance the $current_index to the end of the string
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleQuotedString($state)
    {
        $length = $this->getQuotedStringLength($state->getStatement(), $state->getCurrentIndex(), $state->getCurrentCharacter(), $state->getCharset());
        $state->copyCharacters($length);
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
        if (! is_array($value)) {
            $state->storeValueToBind($name, $value);
            $state->copyIdentifier();
            return $state;
        }
        $placeholder_identifiers = '';
        foreach ($value as $sub) {
            $identifier = $state->storeValueToBind($name, $sub);
            if ($placeholder_identifiers) {
                $placeholder_identifiers .= ', :';
            }
            $placeholder_identifiers .= $identifier;
        }
        $state->passString($name);
        $state->addStringToStatement($placeholder_identifiers);

        return $state;
    }

    /**
     *
     * Replace a question mark by multiple question marks if a numbered placeholder contains an array
     *
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    protected function handleQuestionMark($state)
    {
        $value = $state->getFirstUnusedNumberedValue();
        $state->copyCurrentCharacter();

        if (! is_array($value)) {
            $state->storeNumberedValueToBind($value);
            return $state;
        }
        $placeholder_string = '';
        $nbr = 0;
        foreach ($value as $sub) {
            $state->storeNumberedValueToBind($sub);
            if ($nbr) {
                $placeholder_string .= ', ?';
            }
            $nbr++;
        }
        $state->addStringToStatement($placeholder_string);

        return $state;
   }
}
