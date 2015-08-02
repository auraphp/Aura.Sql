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
     * How many numbered placeholders in the original statement.
     *
     * @var int
     *
     */
    protected $num = 0;

    /**
     *
     * How many numbered placeholders to actually be bound; this may
     * differ from 'num' in that some numbered placeholders may get
     * replaced with quoted CSV strings
     *
     * @var int
     *
     */
    protected $count = 0;

    /**
     *
     * The initial values to be bound.
     *
     * @var array
     *
     */
    protected $values = array();

    /**
     *
     * Named and numbered placeholders to bind at the end.
     *
     * @var array
     *
     */
    protected $final_values = array();

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
        $this->values = $values;

        $final_statement = '';
        for ($current_index = 0, $last_index = mb_strlen($statement, $charset) - 1; $current_index <= $last_index;) {
            $current_character = mb_substr($statement, $current_index, 1, $charset);
            switch ($current_character) {
                case '-':
                    if (mb_substr($statement, $current_index + 1, 1, $charset) === '-') {
                        // One line comment
                        $eol_index = mb_strpos($statement, "\n", $current_index, $charset);
                        if ($eol_index === false) {
                            $eol_index = $last_index;
                        }
                        $final_statement .= mb_substr($statement, $current_index, $eol_index - $current_index, $charset);
                        $current_index = $eol_index;
                    }
                    else{
                        $final_statement .= $current_character;
                        $current_index ++;
                    }
                    break;
                case '/':
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
                    break;
                case "'":
                case '"':
                    $length = $this->getQuotedStringLength($statement, $current_index, $current_character, $charset);
                    $final_statement .= mb_substr($statement, $current_index, $length);
                    $current_index += $length;
                    break;
                case ':':
                    $last_colon = $current_index;
                    while (mb_substr($statement, $last_colon + 1, 1) === ':') {
                        $current_character .= ':';
                        $last_colon ++;
                    }
                    if ($last_colon == $current_index) {
                        // Named placeholder
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
                        $final_statement .= $this->prepareNamedPlaceholder($sub);
                        $current_index = $end_of_placeholder_index;
                    }
                    else {
                        $final_statement .= $current_character;
                        $current_index = $last_colon + 1;
                    }
                    break;
                case '?':
                    $final_statement .= $this->prepareNumberedPlaceholder('?');
                    $current_index ++;
                    break;
                default:
                    $final_statement .= $current_character;
                    $current_index ++;
                    break;
            }
        }
        return array($final_statement, $this->final_values);
    }

    /**
     *
     * Given an array of statement parts, rebuilds each part.
     *
     * @param array $parts The statement parts.
     *
     * @return string The rebuilt statement.
     *
     */
    protected function rebuildParts($parts)
    {
        // loop through the non-quoted parts (0, 3, 6, 9, etc.)
        $k = count($parts);
        for ($i = 0; $i <= $k; $i += 3) {
            $parts[$i] = $this->rebuildPart($parts[$i]);
        }
        return implode('', $parts);
    }

    /**
     *
     * Rebuilds a single statement part.
     *
     * @param string $part The statement part.
     *
     * @return string The rebuilt statement.
     *
     */
    protected function rebuildPart($part)
    {
        // split into subparts by ":name" and "?"
        $subs = preg_split(
            "/(:[a-zA-Z_][a-zA-Z0-9_]*)|(\?)/m",
            $part,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // check subparts to convert bound arrays to quoted CSV strings
        $subs = $this->prepareValuePlaceholders($subs);

        // reassemble
        return implode('', $subs);
    }

    /**
     *
     * Prepares the sub-parts of a query with placeholders.
     *
     * @param array $subs The query subparts.
     *
     * @return array The prepared subparts.
     *
     */
    protected function prepareValuePlaceholders(array $subs)
    {
        foreach ($subs as $i => $sub) {
            $char = substr($sub, 0, 1);
            if ($char == '?') {
                $subs[$i] = $this->prepareNumberedPlaceholder($sub);
            }

            if ($char == ':') {
                $subs[$i] = $this->prepareNamedPlaceholder($sub);
            }
        }

        return $subs;
    }

    /**
     *
     * Bind or quote a numbered placeholder in a query subpart.
     *
     * @param string $sub The query subpart.
     *
     * @return string The prepared query subpart.
     *
     */
    protected function prepareNumberedPlaceholder($sub)
    {
        // what numbered placeholder is this in the original statement?
        $this->num ++;

        // is the corresponding data element an array?
        $bind_array = isset($this->values[$this->num])
                   && is_array($this->values[$this->num]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = '';
            foreach($this->values[$this->num] as $value) {
                $sub .= ( $sub ? ', ' : '') . '?';
                $this->count ++;
                $this->final_values[$this->count] = $value;
            }
        } else {
            // increase the count of numbered placeholders to be bound
            $this->count ++;
            $this->final_values[$this->count] = $this->values[$this->num];
        }

        return $sub;
    }

    /**
     *
     * Bind or quote a named placeholder in a query subpart.
     *
     * @param string $sub The query subpart.
     *
     * @return string The prepared query subpart.
     *
     */
    protected function prepareNamedPlaceholder($sub)
    {
        $name = substr($sub, 1);

        // is the corresponding data element an array?
        $bind_array = isset($this->values[$name])
                   && is_array($this->values[$name]);
        if ($bind_array) {
            $sub = '';
            $index = -1;
            foreach($this->values[$name] as $value){
                do {
                    $index ++;
                    $final_name = $name . '_' . $index;
                } while(isset( $this->final_values[$final_name]));

                if ($sub) {
                    $sub .= ', ';
                }
                $sub .= ':' . $final_name;
                $this->final_values[$final_name] = $value;
            }
        } else {
            // not an array, retain the placeholder for later
            $this->final_values[$name] = $this->values[$name];
        }

        return $sub;
    }

    /**
     *
     * Returns the length of a quoted string part of statement
     *
     * @param string $statement The SQL statement
     * @param int $position Position of the quote character starting the string
     * @param string $quote_character Quote character, ' or "
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
}