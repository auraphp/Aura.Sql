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
namespace Aura\Sql;

/**
 * 
 * Select
 * 
 */
class Select
{
    protected $distinct  = null;
    protected $cols      = array();
    protected $from      = array();
    protected $join      = array();
    protected $where     = array();
    protected $group     = array();
    protected $having    = array();
    protected $order     = array();
    protected $limit     = null; // limit count
    protected $offset    = null; // limit offset
    
    public function __get($key)
    {
        return $this->$key;
    }
    
    public function __set($key, $val)
    {
        $this->$key = $val;
    }
    
    public function __construct()
    {
        $this->cols     = new \ArrayObject;
        $this->from     = new \ArrayObject;
        $this->join     = new \ArrayObject;
        $this->where    = new \ArrayObject;
        $this->group    = new \ArrayObject;
        $this->having   = new \ArrayObject;
        $this->order    = new \ArrayObject;
    }
    
    public function distinct($flag = true)
    {
        $this->distinct = (bool) $flag;
    }
    
    public function cols($spec)
    {
        
    }
    
    public function __toString()
    {
        // is this a SELECT or SELECT DISTINCT?
        if ($this->distinct) {
            $text = "SELECT DISTINCT\n    ";
        } else {
            $text = "SELECT\n    ";
        }
        
        // add columns
        $text .= implode(",\n    ", $this->cols->getArrayCopy()) . "\n";
        
        // from these tables
        $text .= "FROM\n    "
              . implode(",\n    ", $this->from->getArrayCopy())
              . "\n";

        
        // joined to these tables
        $joins = $this->join->getArrayCopy();
        if ($joins) {
            $list = array();
            foreach ($joins as $join) {
                $tmp = '';
                // add the type (LEFT, INNER, etc)
                if (! empty($join['type'])) {
                    $tmp .= $join['type'] . ' ';
                }
                // add the table name and condition
                $tmp .= 'JOIN ' . $join['name'];
                $tmp .= ' ON ' . $join['cond'];
                // add to the list
                $list[] = $tmp;
            }
            // add the list of all joins
            $text .= implode("\n", $list) . "\n";
        }
        
        // with these where conditions
        $where = $this->where->getArrayCopy();
        if ($where) {
            $text .= "WHERE\n    ";
            $text .= implode("\n    ", $where) . "\n";
        }
        
        // grouped by these columns
        $group = $this->group->getArrayCopy();
        if ($group) {
            $text .= "GROUP BY\n    ";
            $text .= implode(",\n    ", $group) . "\n";
        }
        
        // having these conditions
        $having = $this->having->getArrayCopy();
        if ($having) {
            $text .= "HAVING\n    ";
            $text .= implode("\n    ", $having) . "\n";
        }
        
        // ordered by these columns
        $order = $this->order->getArrayCopy();
        if ($order) {
            $text .= "ORDER BY\n    ";
            $text .= implode(",\n    ", $order) . "\n";
        }
        
        // done!
        return $text;
    }
    
    public function clearOrder()
    {
        $this->order = new \ArrayObject;
    }
    
    public function getOrderString()
    {
        return implode(", ", $this->order->getArrayCopy());
    }
}
