<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Query;

/**
 * 
 * A trait for adding RETURNING.
 * 
 * @package Aura.Sql
 * 
 */
trait ReturningTrait
{
    /**
     *
     * The columns to be returned.
     *
     * @var array
     *
     */
    protected $returning = [];

    /**
     *
     * Adds returning columns to the query.
     *
     * Multiple calls to returning() will append to the list of columns, not
     * overwrite the previous columns.
     *
     * @param array $cols The column(s) to add to the query.
     *
     * @return $this
     *
     */
    public function returning(array $cols)
    {
        foreach ($cols as $col) {
            $this->returning[] = $this->connection->quoteNamesIn($col);
        }
        return $this;
    }
    
    /**
     * returns RETURNING query part as string
     * 
     * @return string
     */
    protected function getReturningStatement()
    {
        if (count($this->returning)) {
            $text = 'RETURNING' . $this->stringNewLine;
            $text .= '    ' . implode($this->stringCommaSeparator, $this->returning) . PHP_EOL;
        } else {
            $text = '';
        }
        
        return $text;
    }
}
