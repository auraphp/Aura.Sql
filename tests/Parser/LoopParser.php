<?php
namespace Aura\Sql\Parser;

/**
 * Parser which gets into an infinite loop when finding a 'S' character
 * @package Aura\Sql
 */
class LoopParser extends AbstractParser
{
    /**
     * Constructor. Sets up the bad callback
     */
    public function __construct()
    {
        $this->statementPartsHandlers = array(
            'S' => array($this, 'neverEndingHandler'),
        );
    }

    /**
     * Returns an untouched RebuilderState
     * @param RebuilderState $state
     * @return RebuilderState
     */
    protected function neverEndingHandler($state)
    {
        return $state;
    }
}
