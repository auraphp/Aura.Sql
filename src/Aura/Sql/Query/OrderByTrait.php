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
 * A trait for adding ORDER BY.
 * 
 * @package Aura.Sql
 * 
 */
trait OrderByTrait
{
    /**
     *
     * ORDER BY these columns.
     *
     * @var array
     *
     */
    protected $order_by = [];

    /**
     *
     * Adds a row order to the query.
     *
     * @param array $spec The columns and direction to order by.
     *
     * @return $this
     *
     */
    public function orderBy(array $spec)
    {
        foreach ($spec as $col) {
            $this->order_by[] = $this->connection->quoteNamesIn($col);
        }
        return $this;
    }

    /**
     * returns ORDER BY query part as string
     * 
     * @return string
     */
    protected function getOrderByStatement()
    {
        if ($this->order_by) {
            // newline and indent
            $line = PHP_EOL . '    ';
    
            // comma separator, newline, and indent
            $csep = ',' . $line;
            
            $text = 'ORDER BY' . $line;
            $text .= implode($csep, $this->order_by) . PHP_EOL;
        } else {
            $text = '';
        }
        
        return $text;
    }
}
