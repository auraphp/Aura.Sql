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
 * Basic class used by the parsers. Define some common methods
 *
 * @package Aura\Sql
 *
 */
abstract class BaseParser
{
    /**
     *
     * List of handlers to call when a character is found.
     * The key is the character, the value is a callable which takes a RebuilderState as parameter and returns a RebuilderState
     *
     * @var array
     */
    protected $statementPartsHandlers = array();

    /**
     * @var string $numberedPlaceHolderCharacter Character used to define numbered placeholders. Default is "?"
     */
    protected $numberedPlaceHolderCharacter = "?";

    /**
     * Set a character to be used instead of the "?" character to define numbered placeholders
     * If an handler has been set for the replaced character, set it for the new one
     * @param string $character
     */
    public function setNumberedPlaceholderCharacter($character)
    {
        $this->numberedPlaceHolderCharacter = $character;
        $oldCharacter = $this->getNumberedPlaceholderCharacter();
        if($character !== $oldCharacter){
            $this->statementPartsHandlers[$character] = $this->statementPartsHandlers[$oldCharacter];
            unset($this->statementPartsHandlers[$oldCharacter]);
            $this->numberedPlaceHolderCharacter = $character;
        }
    }

    /**
     * Returns the current character used for numbered placeholders
     * @return string
     */
    public function getNumberedPlaceholderCharacter()
    {
        return $this->numberedPlaceHolderCharacter;
    }

    public function normalize($query)
    {
        $queries = array();
        $charset = 'UTF-8';
        /** @var RebuilderState $state */
        $state = new RebuilderState($query->getString(), $query->getParameters(), $charset);

        $last_check_index = -1;

        while (! $state->done()) {
            if ($state->getCurrentIndex() <= $last_check_index) {
                throw (new \Exception('SQL rebuilder seems to be in an infinite loop.'));
            }
            $last_check_index = $state->getCurrentIndex();

            if (isset($this->statementPartsHandlers[$state->getCurrentCharacter()])) {
                $handler = $this->statementPartsHandlers[$state->getCurrentCharacter()];
                $state = call_user_func($handler, $state);
                // if we encountered a statement separator, we have to prepare a new Query
                if ($state->isNewStatementCharacterFound()) {
                    $queries[] = new Query($state->getStatement(), $state->getValuesToBind());
                    $state->resetFinalStatement();
                }
            }
            else {
                $state->copyCurrentCharacter();
            }
        }
        $queries[] = new Query($state->getStatement(), $state->getValuesToBind());
        return array($queries);
    }
}