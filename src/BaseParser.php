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
     * @var string $numberedPlaceHolderCharacter Character used to define numbered placeholders. Default is "?"
     */
    protected $numberedPlaceHolderCharacter = "?";

    /**
     * Set a character to be used instead of the "?" character to define numbered placeholders
     * @param string $character
     */
    public function setNumberedPlaceholderCharacter($character)
    {
        $this->numberedPlaceHolderCharacter = $character;
    }

    /**
     * Returns the current character used for numbered placeholders
     * @return string
     */
    public function getNumberedPlaceholderCharacter()
    {
        return $this->numberedPlaceHolderCharacter;
    }
}