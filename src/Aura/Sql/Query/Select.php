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
 * An object for SELECT queries.
 *
 * @package Aura.Sql
 *
 */
class Select extends AbstractQuery
{
    use WhereTrait;
    use FlagsTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;

    const FLAG_DISTINCT = 'DISTINCT';

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
     * Is this a SELECT FOR UPDATE?
     *
     * @var
     *
     */
    protected $for_update = false;

    /**
     *
     * The columns to be selected.
     *
     * @var array
     *
     */
    protected $cols = [];

    /**
     *
     * Select from these tables.
     *
     * @var array
     *
     */
    protected $from = [];

    /**
     *
     * A running count of the current $from key.
     *
     * @var int
     *
     */
    protected $from_key = -1;

    /**
     *
     * Use these joins.
     *
     * @var array
     *
     */
    protected $join = [];

    /**
     *
     * GROUP BY these columns.
     *
     * @var array
     *
     */
    protected $group_by = [];

    /**
     *
     * The list of HAVING conditions.
     *
     * @var array
     *
     */
    protected $having = [];

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
        // open the statement
        $text = 'SELECT' . $this->getFlagsAsString() . PHP_EOL;

        // add columns
        if ($this->cols) {
            $text .= $this->indentCsv($this->cols);
        }

        // from and join these sources
        if (count($this->from)) {
            $preparedFrom = [];
            foreach ($this->from as $from) {
                $preparedFrom[] = implode(PHP_EOL, $from);
            }
            $text .= 'FROM' . $this->indentCsv($preparedFrom);
        } else {
            foreach ($this->join as $join) {
                $text .= $join . PHP_EOL;
            }
        }

        // where these conditions
        if ($this->where) {
            $text .= 'WHERE' . $this->indent($this->where);
        }

        // grouped by these columns
        if ($this->group_by) {
            $text .= 'GROUP BY' . $this->indentCsv($this->group_by);
        }

        // having these conditions
        if ($this->having) {
            $text .= 'HAVING' . $this->indent($this->having);
        }

        // order by these columns
        $text .= $this->getOrderByClause();

        // modify with a limit clause per the connection
        $this->connection->limit($text, $this->limit, $this->offset);

        // for update?
        if ($this->for_update) {
            $text .= 'FOR UPDATE' . PHP_EOL;
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
     * @return $this
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
     * Makes the select FOR UPDATE (or not).
     *
     * @param bool $enable Whether or not the SELECT is FOR UPDATE (default
     * true).
     *
     * @return $this
     *
     */
    public function forUpdate($enable = true)
    {
        $this->for_update = (bool) $enable;
    }

    /**
     *
     * Makes the select DISTINCT (or not).
     *
     * @param bool $enable Whether or not the SELECT is DISTINCT (default
     * true).
     *
     * @return $this
     *
     */
    public function distinct($enable = true)
    {
        $this->setFlag(self::FLAG_DISTINCT, $enable);
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
     * @return $this
     *
     */
    public function cols(array $cols)
    {
        foreach ($cols as $col) {
            $this->cols[] = $this->connection->quoteNamesIn($col);
        }
        return $this;
    }

    /**
     *
     * Adds a FROM table and columns to the query.
     *
     * @param string $spec The table specification; "foo" or "foo AS bar".
     *
     * @return $this
     *
     */
    public function from($spec)
    {
        $this->from[] = [$this->connection->quoteName($spec)];
        $this->from_key ++;
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
     * @return $this
     *
     */
    public function fromSubSelect($spec, $name)
    {
        $spec = ltrim(preg_replace('/^/m', '    ', (string) $spec));
        $this->from[] = ["($spec) AS " . $this->connection->quoteName($name)];
        $this->from_key ++;
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
     * @return $this
     *
     */
    public function join($join, $spec, $cond = null)
    {
        $join = strtoupper(ltrim("$join JOIN"));
        $spec = $this->connection->quoteName($spec);
        if ($cond) {
            $cond = $this->connection->quoteNamesIn($cond);
            $clause = "$join $spec ON $cond";
        } else {
            $clause = "$join $spec";
        }

        $this->join[] = $clause;

        // connect to latest from statement
        if ($this->from) {
            $this->from[$this->from_key][] = $clause;
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
     * @return $this
     *
     */
    public function joinSubSelect($join, $spec, $name, $cond = null)
    {
        $join = strtoupper(ltrim("$join JOIN"));
        $spec = ltrim(preg_replace('/^/m', '    ', (string) $spec));
        $name = $this->connection->quoteName($name);
        if ($cond) {
            $cond = $this->connection->quoteNamesIn($cond);
            $clause = "$join ($spec) AS $name ON $cond";
        } else {
            $clause = "$join ($spec) AS $name";
        }

        $this->join[] = $clause;

        // connect to latest from statement
        if ($this->from) {
            $this->from[$this->from_key][] = $clause;
        }

        return $this;
    }

    /**
     *
     * Adds grouping to the query.
     *
     * @param array $spec The column(s) to group by.
     *
     * @return $this
     *
     */
    public function groupBy(array $spec)
    {
        foreach ($spec as $col) {
            $this->group_by[] = $this->connection->quoteNamesIn($col);
        }
        return $this;
    }

    /**
     *
     * Adds a HAVING condition to the query by AND; if a value is passed as
     * the second param, it will be quoted and replaced into the condition
     * wherever a question-mark appears.
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
     * @return $this
     *
     */
    public function having($cond)
    {
        $cond = $this->connection->quoteNamesIn($cond);

        if (func_num_args() > 1) {
            $cond = $this->connection->quoteInto($cond, func_get_arg(1));
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
     * Adds a HAVING condition to the query by AND; otherwise identical to
     * `having()`.
     *
     * @param string $cond The HAVING condition.
     *
     * @return $this
     *
     * @see having()
     *
     */
    public function orHaving($cond)
    {
        $cond = $this->connection->quoteNamesIn($cond);

        if (func_num_args() > 1) {
            $cond = $this->connection->quoteInto($cond, func_get_arg(1));
        }

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
     * Sets the limit and count by page number.
     *
     * @param int $page Limit results to this page number.
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
        $this->resetFlags();
        $this->cols       = [];
        $this->from       = [];
        $this->from_key   = -1;
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
