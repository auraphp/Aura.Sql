<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql\Parser;

/**
 * Query parser for Microsoft SQL Server flavored queries
 * @package Aura\Sql
 */
class SqlsrvParser extends AbstractParser
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
            "[" => array($this, 'handleIdentifier'),
            ':' => array($this, 'handleColon'),
            '?' => array($this, 'handleNumberedParameter'),
            ';' => array($this, 'handleSemiColon'),
        );
    }

    /**
     *
     * Handles `[table.col]` (etc.) identifiers.
     *
     * @param State $state
     *
     * @return State
     */
    protected function handleArray($state)
    {
        $state->copyUntilCharacter(']');
        return $state;
    }
}
