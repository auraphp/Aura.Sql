<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;
use Aura\Sql\Exception\MissingParameter;

/**
 *
 * This support class is used to store the current rebuilding state
 *
 * @package Aura.Sql
 *
 */
class RebuilderState
{
    /**
     * @var string Base SQL query
     */
    protected $statement;

    /**
     * @var array Raw values to bind
     */
    protected $values;

    /**
     * @var string Current state of the query we expect to send to PDO
     */
    protected $final_statement;

    /**
     * @var array Array containing each individual values to bind
     */
    protected $values_to_bind;

    /**
     * @var int Current position in the base query we are checking
     */
    protected $current_index;

    /**
     * @var int Index of the last character of the base query
     */
    protected $last_index;

    /**
     * @var string Character set of the query
     */
    protected $charset;

    /**
     * @var int Index of the next numbered placeholder to use
     */
    protected $numbered_placeholder_index;

    /**
     * @var int Last index of the numbered placeholder stored in the $values_to_bind array
     */
    protected $final_numbered_placeholder_index;

    /**
     * @var bool Stores if the character use to separate statements has been found
     */
    protected $new_statement_character_found = false;

    /**
     *
     * Constructor
     *
     * @param string $statement The base SQL query
     * @param array $values List of values we want to bind
     * @param string $charset Character set used for the query. Defaults to 'UTF-8'
     */
    public function __construct($statement, $values = array(), $charset = 'UTF-8')
    {
        $this->statement = $statement;
        $this->values = $values;
        $this->charset = $charset;
        $this->last_index = mb_strlen($statement, $charset) - 1;
        $this->current_index = 0;
        $this->numbered_placeholder_index = 1;
        $this->resetFinalStatement();
        // PDO numbered parameters start at index 1
        if (array_key_exists(0, $this->values)) {
            array_unshift($this->values, null);
        }
    }

    /**
     * Set en empty final statement. Used when starting and when finding the end of a statement
     */
    public function resetFinalStatement()
    {
        $this->final_statement = '';
        $this->values_to_bind = array();
        $this->final_numbered_placeholder_index = 1;
        $this->new_statement_character_found = false;
    }

    /**
     * @return boolean
     */
    public function isNewStatementCharacterFound()
    {
        return $this->new_statement_character_found;
    }

    /**
     * @param boolean $new_statement_character_found
     */
    public function setNewStatementCharacterFound($new_statement_character_found)
    {
        $this->new_statement_character_found = $new_statement_character_found;
    }

    /**
     *
     * Returns if all the characters in the original query have been checked
     *
     * @return bool
     */
    public function done()
    {
        return $this->current_index > $this->last_index;
    }

    /**
     *
     * Returns if next characters from the current character are the string given as a parameter
     *
     * @param string $characters
     *
     * @return bool
     */
    public function nextCharactersAre($characters)
    {
        return mb_substr($this->statement, $this->current_index + 1, mb_strlen($characters, $this->charset), $this->charset) === $characters;
    }

    /**
     *
     * Returns the current character
     *
     * @return string
     */
    public function getCurrentCharacter()
    {
        return mb_substr($this->statement, $this->current_index, 1, $this->charset);
    }

    /**
     *
     * Returns the modified SQL query
     *
     * @return string
     */
    public function getFinalStatement()
    {
        return $this->final_statement;
    }

    /**
     *
     * Returns an array with all the values to bind
     *
     * @return array
     */
    public function getValuesToBind()
    {
        return $this->values_to_bind;
    }

    /**
     *
     * Returns the value of a named bound parameter as given in the constructor
     *
     * @param $name
     *
     * @return null
     */
    public function getNamedParameterValue($name)
    {
        return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    /**
     *
     * Copy the current character from the original query to the final query and advance the current index by one
     *
     */
    public function copyCurrentCharacter()
    {
        $this->final_statement .= $this->getCurrentCharacter();
        $this->current_index ++;
    }

    /**
     *
     * Copy $nbr characters from the original query to the final query and advance the current index by $nbr
     *
     * @param $nbr
     */
    public function copyCharacters($nbr)
    {
        if ($nbr <= 0) {
            return;
        }
        $this->final_statement .= mb_substr($this->statement, $this->current_index, $nbr, $this->charset);
        $this->current_index += $nbr;
    }

    /**
     *
     * If the current index is the start of a placeholder identifier, copy the identifier from the original query to the final one
     * and advance the current by the length of the identifier.
     *
     */
    public function copyIdentifier()
    {
        $identifier = $this->getIdentifier();
        $identifier_length = mb_strlen($identifier, $this->charset);
        $this->copyCharacters($identifier_length);
    }

    /**
     *
     * Advance the current index by $string length with no copy
     *
     * @param string $string
     */
    public function passString($string)
    {
        $string_length = mb_strlen($string, $this->charset);
        $this->current_index += $string_length;
    }

    /**
     *
     * Concatenate $string to the final query
     *
     * @param string $string
     */
    public function addStringToStatement($string)
    {
        $this->final_statement .= $string;
    }


    /**
     *
     * Copy everything from current index to the end of a substring matching $character
     *
     * @param string $character
     */
    public function copyUntilCharacter($character)
    {
        $length = mb_strlen($character, $this->charset);
        $index = mb_strpos($this->statement, $character, $this->current_index, $this->charset);
        if ($index === false) {
            $end_index = $this->last_index;
        }
        else {
            $end_index = $index + $length;
        }

        $this->final_statement .= mb_substr($this->statement, $this->current_index, $end_index - $this->current_index, $this->charset);
        $this->current_index = $end_index;
    }

    /**
     *
     * Returns the original SQL query
     *
     * @return string
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     *
     * Returns the current index in the original SQL query
     *
     * @return int
     */
    public function getCurrentIndex()
    {
        return $this->current_index;
    }


    /**
     *
     * Returns the character set in use
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     *
     * Returns an identifier starting at the current index
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->capture('\\w+\\b');
    }

    /**
     *
     * Tries to matche a regular expression starting at current index and returns the result
     *
     * @param string $regexp
     * @param int $capture_group
     * @return string
     */
    public function capture($regexp, $capture_group = 0){
        $capture = '';
        if ($this->last_index <= $this->current_index) {
           return $capture;
        }
        mb_regex_encoding($this->charset);
        if (mb_ereg_search_init($this->statement) !== false) {
            mb_ereg_search_setpos($this->current_index);
            if ($matches = mb_ereg_search_regs('\\G' . $regexp)) {
                $capture = isset($matches[$capture_group]) ? $matches[$capture_group] : '';
            }
        }
        return $capture;
    }

    /**
     *
     * Stores a value in the final array of values to bind. If the name is already in use, generate a new name
     *
     * @param string $name
     * @param mixed $value
     *
     * @return string
     */
    public function storeValueToBind($name, $value)
    {
        $final_name = $name;
        $index = 0;
        while(isset($this->values_to_bind[$final_name])){
            $final_name = $name . '_' . $index;
            $index++;
        }
        $this->values_to_bind[$final_name] = $value;
        return $final_name;
    }


    /**
     *
     * Returns the last unused numbered parameter value
     *
     * @return $value
     *
     * @throws MissingParameter
     *
     */
    public function getFirstUnusedNumberedValue()
    {
        if (array_key_exists($this->numbered_placeholder_index, $this->values) === false) {
            throw new MissingParameter('Parameter ' . $this->numbered_placeholder_index . ' is missing from the bound values');
        }
        $value = $this->values[$this->numbered_placeholder_index];
        $this->numbered_placeholder_index ++;
        return $value;
    }

    /**
     *
     * Stores a numbered parameter in the array of bound values
     *
     * @param $value
     */
    public function storeNumberedValueToBind($value)
    {
        $this->values_to_bind[$this->final_numbered_placeholder_index] = $value;
        $this->final_numbered_placeholder_index ++;
    }
}
