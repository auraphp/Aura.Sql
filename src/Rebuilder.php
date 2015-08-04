<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
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
     * The key is the character, the value is a callable which takes seven parameters and returns an array:
     * - string $statement The original statement
     * - array $values The original list of parameter values
     * - string $final_statement The final statement
     * - array $final_values The array of values to bind
     * - int $current_index The index of the character
     * - int $current_character The character
     * - int $last_index The last index of the original statement
     * - string $charset The statement charset
     * - int $num How many numbered placeholders in the original statement
     * - int $count How many numbered placeholders to actually be bound
     * The returned array contains three items:
     * - at index 0, the modified statement
     * - at index 1, the modified values array
     * - at index 2, the index of next character to handle
     * - at index 3, the index of the last numbered placeholder from the original statement used
     * - at index 4, the index of the last numbered placeholder to bind
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
     */
    public function rebuildStatement($statement, $values = array(), $charset = 'UTF-8')
    {
        // match standard PDO execute() behavior of zero-indexed arrays
        if (array_key_exists(0, $values)) {
            array_unshift($values, null);
        }

        $final_statement = '';
        $final_values = array();
        $last_index = mb_strlen($statement, $charset) - 1;
        $current_index = 0;
        $num = 0;
        $count = 0;
        while ($current_index <= $last_index) {
            $current_character = mb_substr($statement, $current_index, 1, $charset);
            if (isset($this->statementPartsHandlers[$current_character])) {
                $handler = $this->statementPartsHandlers[$current_character];
                list($final_statement, $final_values, $current_index, $num, $count) = call_user_func($handler,
                    $statement,
                    $values,
                    $final_statement,
                    $final_values,
                    $current_index,
                    $current_character,
                    $last_index,
                    $charset,
                    $num,
                    $count
                );
            }
            else {
                $final_statement .= $current_character;
                $current_index ++;
            }
        }
        return array($final_statement, $final_values);
    }

    /**
     *
     * Bind or quote a numbered placeholder in a query subpart
     *
     * @param array $values The original array of values
     *
     * @param int $num The last index of a numbered placeholder added to the statement
     *
     * @param array $final_values The current modified array of values to effectively bind
     *
     * @param int $count The last index of a numbered placeholder in the modified array of values
     *
     * @return array An array which has at index 0 the modified subpart, at index 1 the final values to bind, at index 2
     * the current index of the last numbered placeholder used from the original values array and at index 3 the index of the last
     * numbered placeholder in the final values array
     *
     */
    protected function prepareNumberedPlaceholder($values, $num, $final_values, $count)
    {
        // what numbered placeholder is this in the original statement?
        $num ++;

        // is the corresponding data element an array?
        $bind_array = isset($values[$num])
                   && is_array($values[$num]);
        if ($bind_array) {
            // PDO won't bind an array; replace placeholder with multiple placeholder, one for each value in the array
            $sub = '';
            foreach($values[$num] as $value) {
                $sub .= ( $sub ? ', ' : '') . '?';
                $count ++;
                $final_values[$count] = $value;
            }
        } else {
            // increase the count of numbered placeholders to be bound
            $count ++;
            $final_values[$count] = $values[$num];
            $sub = '?';
        }

        return array($sub, $final_values, $num, $count);
    }

    /**
     *
     * Bind or quote a named placeholder in a query subpart
     *
     * @param string $sub The query subpart
     *
     * @param array $values The original array of values to bind
     *
     * @param array $final_values The modified array of values which will be bound
     *
     * @param string $charset
     *
     * @return array An array containing at index 0 the possibly modified subpart and at index 1 the array of values to bind
     *
     */
    protected function prepareNamedPlaceholder($sub, $values, $final_values, $charset)
    {
        $name = mb_substr($sub, 1, null, $charset);

        // is the corresponding data element an array?
        $bind_array = isset($values[$name])
                   && is_array($values[$name]);
        if ($bind_array) {
            $sub = '';
            $index = -1;
            foreach($values[$name] as $value){
                do {
                    $index ++;
                    $final_name = $name . '_' . $index;
                } while(isset($final_values[$final_name]));

                if ($sub) {
                    $sub .= ', ';
                }
                $sub .= ':' . $final_name;
                $final_values[$final_name] = $value;
            }
        } else {
            // not an array, retain the placeholder for later
            $final_values[$name] = $values[$name];
        }

        return array($sub, $final_values);
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
        while (!$end_found)
        {
            $end_of_quoted_string_index = mb_strpos($statement, $quote_character, $position + 1, $charset);
            if ($end_of_quoted_string_index === false) {
                return $last_index - $start;
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
                if ($escape_count % 2 == 0)
                {
                    return $end_of_quoted_string_index + $escape_count - $start + 1;
                }
                $position = $end_of_quoted_string_index + $escape_count;
            }
            else {
                $position = $end_of_quoted_string_index;
            }
        }
    }

    /**
     *
     * Returns a modified statement, values and current index depending on what follow a '-' character.
     *
     * @param string $statement
     * @param array $values
     * @param string $final_statement
     * @param array $final_values
     * @param int $current_index
     * @param string $current_character
     * @param int $last_index
     * @param string $charset
     * @param int $num
     * @param int $count
     *
     * @return array
     */
    protected function handleSingleLineComment($statement, $values, $final_statement, $final_values, $current_index, $current_character, $last_index, $charset, $num, $count)
    {
        if (mb_substr($statement, $current_index + 1, 1, $charset) === '-') {
            // One line comment
            $eol_index = mb_strpos($statement, "\n", $current_index, $charset);
            if ($eol_index === false) {
                $eol_index = $last_index;
            }
            $final_statement .= mb_substr($statement, $current_index, $eol_index - $current_index, $charset);
            $current_index = $eol_index;
        }
        else {
            $final_statement .= $current_character;
            $current_index ++;
        }

        return array($final_statement, $final_values, $current_index, $num, $count);
    }

    /**
     *
     * If the character following a '/' one is a '*', advance the $current_index to the end of this multiple line comment
     *
     * @param string $statement
     * @param array $values
     * @param string $final_statement
     * @param array $final_values
     * @param int $current_index
     * @param string $current_character
     * @param int $last_index
     * @param string $charset
     * @param int $num
     * @param int $count
     *
     * @return array
     */
    protected function handleMultiLineComment($statement, $values, $final_statement, $final_values, $current_index, $current_character, $last_index, $charset, $num, $count)
    {
        if (mb_substr($statement, $current_index + 1, 1, $charset) === '*') {
            // Multi line comment
            $end_of_comment_index = mb_strpos($statement, "*/", $current_index, $charset);
            if ($end_of_comment_index === false) {
                $end_of_comment_index = $last_index;
            }
            else {
                $end_of_comment_index += 2;
            }

            $final_statement .= mb_substr($statement, $current_index, $end_of_comment_index - $current_index, $charset);
            $current_index = $end_of_comment_index;
        }
        else{
            $final_statement .= $current_character;
            $current_index ++;
        }

        return array($final_statement, $final_values, $current_index, $num, $count);
    }

    /**
     *
     * After a single quote or double quote string, advance the $current_index to the end of the string
     *
     * @param string $statement
     * @param array $values
     * @param string $final_statement
     * @param array $final_values
     * @param int $current_index
     * @param string $current_character
     * @param int $last_index
     * @param string $charset
     * @param int $num
     * @param int $count
     *
     * @return array
     */
    protected function handleQuotedString($statement, $values, $final_statement, $final_values, $current_index, $current_character, $last_index, $charset, $num, $count)
    {
        $length = $this->getQuotedStringLength($statement, $current_index, $current_character, $charset);
        $final_statement .= mb_substr($statement, $current_index, $length, $charset);
        $current_index += $length;
        return array($final_statement, $final_values, $current_index, $num, $count);
    }

    /**
     *
     * Check if a ':' colon character is followed by what can be a named placeholder.
     *
     * @param string $statement
     * @param array $values
     * @param string $final_statement
     * @param array $final_values
     * @param int $current_index
     * @param string $current_character
     * @param int $last_index
     * @param string $charset
     * @param int $num
     * @param int $count
     *
     * @return array
     */
    protected function handleColon($statement, $values, $final_statement, $final_values, $current_index, $current_character, $last_index, $charset, $num, $count)
    {
        $last_colon = $current_index;
        while (mb_substr($statement, $last_colon + 1, 1, $charset) === ':') {
            $current_character .= ':';
            $last_colon ++;
        }
        if ($last_colon == $current_index) {
            // Named placeholder
            mb_regex_encoding($charset);
            $end_of_placeholder_index = $last_index;
            if (mb_ereg_search_init($statement) !== false) {
                $end_of_placeholder_index = $last_index + 1;
                mb_ereg_search_setpos($current_index + 1);
                if (mb_ereg_search('\\W')) {
                    $pos = mb_ereg_search_getpos();
                    $end_of_placeholder_index = $pos - 1;
                }
            }

            $sub = mb_substr($statement, $current_index, $end_of_placeholder_index - $current_index);
            list($sub, $final_values) = $this->prepareNamedPlaceholder($sub, $values, $final_values, $charset);
            $final_statement .= $sub;
            $current_index = $end_of_placeholder_index;
        }
        else {
            $final_statement .= $current_character;
            $current_index = $last_colon + 1;
        }

        return array($final_statement, $final_values, $current_index, $num, $count);
    }

    /**
     *
     * Replace a question mark by multiple question marks if a numbered placeholder contains an array
     *
     * @param string $statement
     * @param array $values
     * @param string $final_statement
     * @param array $final_values
     * @param int $current_index
     * @param string $current_character
     * @param int $last_index
     * @param string $charset
     * @param int $num
     * @param int $count
     *
     * @return array
     */
    protected function handleQuestionMark($statement, $values, $final_statement, $final_values, $current_index, $current_character, $last_index, $charset, $num, $count)
    {
        list($sub, $final_values, $num, $count) = $this->prepareNumberedPlaceholder($values, $num, $final_values, $count);
        $final_statement .= $sub;
        $current_index ++;
        return array($final_statement, $final_values, $current_index, $num, $count);
    }
}