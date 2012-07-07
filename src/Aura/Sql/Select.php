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
use Aura\Sql\Adapter\AbstractAdapter;

/**
 * 
 * Select
 * 
 * @package Aura.Sql
 * 
 */
class Select
{
    /**
     * 
     * A constant so we can find "ignored" params, to avoid func_num_args().
     * 
     * The md5() value of 'Solar_Sql_Select::IGNORE', so it should be unique.
     * 
     * Yes, this is hackery, and perhaps a micro-optimization at that.
     * 
     * @const
     * 
     */
    const IGNORE = '--5a333dc50d9341d8e73e56e2ba591b87';
    
    /**
     * 
     * An array of union SELECT statements.
     * 
     * @var array
     * 
     */
    protected $union = [];
    
    /**
     * 
     * The component parts of the current select statement.
     * 
     * @var array
     * 
     */
    protected $distinct   = false;
    
    /**
     * 
     * columns to the query.
     * 
     * @var array
     * 
     */
    protected $cols       = [];
    
    /**
     * 
     * FROM table and columns to the query
     * 
     * @var array
     * 
     */
    protected $from       = [];
    
    /**
     * 
     * JOIN table and columns to the query.
     * 
     * @var array
     * 
     */
    protected $join       = [];
    
    /**
     * 
     * a WHERE condition to the query
     * 
     * @var array
     * 
     */
    protected $where      = [];
    
    /**
     * 
     * grouping to the query.
     * 
     * @var array
     * 
     */
    protected $group_by   = [];
    
    /**
     * 
     * a HAVING condition to the query
     * 
     * @var array
     * 
     */
    protected $having     = [];
    
    /**
     * 
     * a row order to the query.
     * 
     * @var array
     * 
     */
    protected $order_by   = [];
    
    /**
     * 
     * a limit count on the query
     * 
     * @var int
     * 
     */
    protected $limit      = 0;
    
    /**
     * 
     * Sets a limit offset on the query.
     * 
     * @var int
     * 
     */
    protected $offset     = 0;
    
    // FIXME
    /**
     * 
     * Mark query is for update
     * 
     * @var type
     * 
     */
    protected $for_update = false;
    
    /**
     * 
     * The number of rows per page.
     * 
     * @var int
     * 
     */
    protected $paging = 10;
    
    /**
     * 
     * An SQL connection adapter.
     * 
     * @var AbstractAdapter
     * 
     */
    protected $sql;
    
    /**
     * 
     * Constructor.
     * 
     * @param AbstractAdapter $sql An SQL adapter.
     * 
     * @return void
     * 
     */
    public function __construct(AbstractAdapter $sql)
    {
        $this->sql = $sql;
    }
    
    /**
     * 
     * Returns this object as an SQL statement string.
     * 
     * @return string An SQL statement string.
     * 
     */
    
    public function __toString()
    {
        if ($this->union) {
            return implode(PHP_EOL, $this->union) . PHP_EOL . $this->toString();
        } else {
            return $this->toString();
        }
    }
    
    /**
     * 
     * Returns the SELECT parts composed as a string (does not include
     * the union selects).
     * 
     * @return string A SELECT string.
     * 
     */
    protected function toString()
    {
        // newline and indent
        $line = PHP_EOL . '    ';
        
        // comma separator, newline, and indent
        $csep = ',' . $line;
        
        // open the statement
        if ($this->distinct) {
            $text = 'SELECT DISTINCT' . PHP_EOL;
        } else {
            $text = 'SELECT' . PHP_EOL;
        }
        
        // add columns
        if ($this->cols) {
            $text .= $line . implode($csep, $this->cols) . PHP_EOL;
        }
        
        // from these sources
        if ($this->from) {
            $text .= 'FROM' . $line;
            $text .= implode($csep, $this->from) . PHP_EOL;
        }
        
        // join these sources
        foreach ($this->join as $join) {
            $text .= $join . PHP_EOL;
        }
        
        // where these conditions
        if ($this->where) {
            $text .= 'WHERE' . $line;
            $text .= implode($line, $this->where) . PHP_EOL;
        }
        
        // grouped by these columns
        if ($this->group_by) {
            $text .= 'GROUP BY' . $line;
            $text .= implode($csep, $this->group_by) . PHP_EOL;
        }
        
        // having these conditions
        if ($this->having) {
            $text .= 'HAVING' . $line;
            $text .= implode($line, $this->having) . PHP_EOL;
        }
        
        // ordered by these columns
        if ($this->order_by) {
            $text .= 'ORDER BY' . $line;
            $text .= implode($csep, $this->order_by) . PHP_EOL;
        }
        
        // modify with a limit clause per the adapter
        $this->sql->limit($text, $this->limit, $this->offset) . PHP_EOL;
        
        // for update?
        if ($this->for_update) {
            $text .= "FOR UPDATE" . PHP_EOL;
        }
        
        // done!
        return $text;
    }
    
    /**
     * 
     * Sets the number of rows per page.
     * 
     * @param int $paging The number of rows to page at.
     * 
     * @return self
     * 
     */
    public function setPaging($paging)
    {
        $this->paging = (int) $paging;
        return $this;
    }
    
    /**
     * 
     * Gets the number of rows per page.
     * 
     * @return int The number of rows per page.
     * 
     */
    public function getPaging()
    {
        return $this->paging;
    }
    
    /**
     * 
     * Makes the select DISTINCT (or not).
     * 
     * @param bool $flag Whether or not the SELECT is DISTINCT (default
     * true).
     * 
     * @return self
     * 
     */
    public function distinct($flag = true)
    {
        $this->distinct = (bool) $flag;
        return $this;
    }
    
    /**
     * 
     * Adds columns to the query.
     * 
     * Multiple calls to cols() will append to the list of columns, not
     * overwrite the previous columns.
     * 
     * @param array $cols The column(s) to add to the query.
     * 
     * @return self
     * 
     */
    public function cols(array $cols)
    {
        foreach ($cols as $col) {
            $this->cols[] = $this->sql->quoteNamesIn($col);
        }
        return $this;
    }
    
    /**
     * 
     * Adds a FROM table and columns to the query.
     * 
     * @param string $spec The table specification; "foo" or "foo AS bar".
     * 
     * @return self
     * 
     */
    public function from($spec)
    {
        $this->from[] = $this->sql->quoteName($spec);
        return $this;
    }
    
    /**
     * 
     * Adds an aliased sub-select to the query.
     * 
     * @param string|Select $spec If a Select object, use as the sub-select;
     * if a string, the sub-select string.
     * 
     * @param string $name The alias name for the sub-select.
     * 
     * @return self
     * 
     */
    public function fromSubSelect($spec, $name)
    {
        $spec = ($spec instanceof Select) ? $spec->__toString() : $spec;
        $this->from[] = "($spec) AS " . $this->sql->quoteName($name);
        return $this;
    }
    
    /**
     * 
     * Adds a JOIN table and columns to the query.
     * 
     * @param string $join The join type: inner, left, natural, etc.
     * 
     * @param string $spec The table specification; "foo" or "foo AS bar".
     * 
     * @param string $cond Join on this condition.
     * 
     * @return self
     * 
     */
    public function join($join, $spec, $cond = null)
    {
        $join = strtoupper(ltrim("$join JOIN"));
        $spec = $this->sql->quoteName($spec);
        if ($cond) {
            $cond = $this->sql->quoteNamesIn($cond);
            $this->join[] = "$join $spec ON $cond";
        } else {
            $this->join[] = "$join $spec";
        }
        return $this;
    }
    
    /**
     * 
     * Adds a JOIN to an aliased subselect and columns to the query.
     * 
     * @param string $join The join type: inner, left, natural, etc.
     * 
     * @param string|Select $spec If a Select
     * object, use as the sub-select; if a string, the sub-select
     * command string.
     * 
     * @param string $name The alias name for the sub-select.
     * 
     * @param string $cond Join on this condition.
     * 
     * @return self
     * 
     */
    public function joinSubSelect($join, $spec, $name, $cond = null)
    {
        $join = strtoupper(ltrim("$join JOIN"));
        $spec = ($spec instanceof Select) ? $spec->__toString() : $spec;
        $name = $this->sql->quoteName($name);
        if ($cond) {
            $cond = $this->sql->quoteNamesIn($cond);
            $this->join[] = "$join ($spec) AS $name ON $cond";
        } else {
            $this->join[] = "$join ($spec) AS $name";
        }
        return $this;
    }
    
    /**
     * 
     * Adds a WHERE condition to the query by AND.
     * 
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears.
     * 
     * Array values are quoted and comma-separated.
     * 
     * @param string $cond The WHERE condition.
     * 
     * @param string $val A value to quote into the condition.
     * 
     * @return self
     * 
     */
    public function where($cond, $val = self::IGNORE)
    {
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        if ($this->where) {
            $this->where[] = "AND $cond";
        } else {
            $this->where[] = $cond;
        }
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds a WHERE condition to the query by OR.
     * 
     * Otherwise identical to where().
     * 
     * @param string $cond The WHERE condition.
     * 
     * @param string $val A value to quote into the condition.
     * 
     * @return self
     * 
     * @see where()
     * 
     */
    public function orWhere($cond, $val = self::IGNORE)
    {
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        if ($this->where) {
            $this->where[] = "OR $cond";
        } else {
            $this->where[] = $cond;
        }
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds grouping to the query.
     * 
     * @param array $spec The column(s) to group by.
     * 
     * @return self
     * 
     */
    public function groupBy(array $spec)
    {
        foreach ($spec as $col) {
            $this->group_by[] = $this->sql->quoteNamesIn($col);
        }
        return $this;
    }
    
    /**
     * 
     * Adds a HAVING condition to the query by AND.
     * 
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears.
     * 
     * Array values are quoted and comma-separated.
     * 
     * {{code: php
     *     // simplest but non-secure
     *     $select->having("COUNT(id) = $count");
     *     
     *     // secure
     *     $select->having('COUNT(id) = ?', $count);
     *     
     *     // equivalent security with named binding
     *     $select->having('COUNT(id) = :count');
     *     $select->bind('count', $count);
     * }}
     * 
     * @param string $cond The HAVING condition.
     * 
     * @param string $val A value to quote into the condition.
     * 
     * @return self
     * 
     */
    public function having($cond, $val = self::IGNORE)
    {
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        if ($this->having) {
            $this->having[] = "AND $cond";
        } else {
            $this->having[] = $cond;
        }
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds a HAVING condition to the query by OR.
     * 
     * Otherwise identical to orHaving().
     * 
     * @param string $cond The HAVING condition.
     * 
     * @param string $val A value to quote into the condition.
     * 
     * @return self
     * 
     * @see having()
     * 
     */
    public function orHaving($cond, $val = self::IGNORE)
    {
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($this->having) {
            $this->having[] = "OR $cond";
        } else {
            $this->having[] = $cond;
        }
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds a row order to the query.
     * 
     * @param array $spec The columns and direction to order by.
     * 
     * @return self
     * 
     */
    public function orderBy(array $spec)
    {
        foreach ($spec as $col) {
            $this->order_by[] = $this->sql->quoteNamesIn($col);
        }
        return $this;
    }
    
    /**
     * 
     * Sets a limit count on the query.
     * 
     * @param int $limit The number of rows to return.
     * 
     * @return self
     * 
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }
    
    // FIXME
    /**
     * 
     * Mark as query is for update
     * 
     * @param bool $flag
     * 
     * @return void
     * 
     */
    public function forUpdate($flag = true)
    {
        $this->for_update = (bool) $flag;
    }
    
    /**
     * 
     * Sets a limit offset on the query.
     * 
     * @param int $offset Start returning after this many rows.
     * 
     * @return self
     * 
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }
    
    /**
     * 
     * Sets the limit and count by page number.
     * 
     * @param int $page Limit results to this page number.
     * 
     * @return self
     * 
     */
    public function page($page)
    {
        // reset the count and offset
        $this->limit  = 0;
        $this->offset = 0;
        
        // determine the count and offset from the page number
        $page = (int) $page;
        if ($page > 0) {
            $this->limit  = $this->paging;
            $this->offset = $this->paging * ($page - 1);
        }
        
        // done
        return $this;
    }
    
    /**
     * 
     * Takes the current select properties and retains them, then sets
     * UNION for the next set of properties.
     * 
     * @return self
     * 
     */
    public function union()
    {
        $this->union[] = $this->toString() . PHP_EOL . 'UNION';
        $this->reset();
        return $this;
    }
    
    /**
     * 
     * Takes the current select properties and retains them, then sets
     * UNION ALL for the next set of properties.
     * 
     * @return self
     * 
     */
    public function unionAll()
    {
        $this->union[] = $this->toString() . PHP_EOL . 'UNION ALL';
        $this->reset();
        return $this;
    }
    
    /**
     * 
     * Clears the current select properties; generally used after adding a
     * union.
     * 
     * @return void
     * 
     */
    protected function reset()
    {
        $this->distinct   = false;
        $this->cols       = [];
        $this->from       = [];
        $this->join       = [];
        $this->where      = [];
        $this->group_by   = [];
        $this->having     = [];
        $this->order_by   = [];
        $this->limit      = 0; 
        $this->offset     = 0;
        $this->for_update = false;
    }
}
