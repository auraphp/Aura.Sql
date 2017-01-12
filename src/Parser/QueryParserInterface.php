<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql\Parser;

interface QueryParserInterface
{
    /**
     * Normalize a query and its parameters to adapt it to PDO's limitations and returns a list of queries
     * @param Query $query
     * @return Query[]
     */
    public function normalize($query);

    /**
     * Set a character to be used instead of the "?" character to define numbered placeholders
     * @param string $character
     */
    public function setNumberedPlaceholderCharacter($character);

    /**
     * Returns the current character used for numbered placeholders
     * @return string
     */
    public function getNumberedPlaceholderCharacter();
}
