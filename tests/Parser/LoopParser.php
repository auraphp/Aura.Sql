<?php
namespace Aura\Sql\Parser;

/**
 * Parser which gets into an infinite loop when finding a 'S' character
 */
class LoopParser extends AbstractParser
{
    /**
     * Constructor. Sets up the bad callback
     */
    public function __construct()
    {
        $this->handlers = array(
            'S' => 'neverEndingHandler',
        );
    }

    /**
     * Returns an untouched State
     * @param State $state The parser state.
     */
    protected function neverEndingHandler($state)
    {
    }
}
