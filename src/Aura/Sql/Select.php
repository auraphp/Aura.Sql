<?php
namespace Aura\Sql;
use Aura\Sql\Adapter\AbstractAdapter;
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
     * An array of compound SELECT statements.
     * 
     * @var array
     * 
     */
    protected $compound = [];
    
    /**
     * 
     * The component parts of the current select statement.
     * 
     * @var array
     * 
     */
    protected $parts = array(
        'distinct'  => false,
        'cols'      => [],
        'from'      => [],
        'join'      => [],
        'where'     => [],
        'group_by'  => [],
        'having'    => [],
        'order_by'  => [],
        'limit'     => ['count'  => 0, 'offset' => 0],
    );
    
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
     * Column sources, typically "from", "select", and "join".
     * 
     * We use this for automated deconfliction of column names.
     * 
     * @var array
     * 
     */
    protected $sources = [];
    
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
        return implode('', $this->compound) . $this->toString();
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
        // force a positive integer
        $paging = (int) $paging;
        if ($paging < 1) {
            $paging = 1;
        }
        $this->paging = $paging;
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
        $this->parts['distinct'] = (bool) $flag;
        return $this;
    }
    
    /**
     * 
     * Adds 1 or more columns to the SELECT, without regard to a FROM or JOIN.
     * 
     * Multiple calls to cols() will append to the list of columns, not
     * overwrite the previous columns.
     * 
     * @param array $cols The column(s) to add to the SELECT.
     * 
     * @return self
     * 
     */
    public function cols(array $cols)
    {
        // save in the sources list
        $this->addSource(
            'cols',
            null,
            null,
            null,
            null,
            $cols
        );
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds a FROM table and columns to the query.
     * 
     * @param string $spec The table specification; "foo" or "foo AS bar".
     * 
     * @param array $cols The columns to select from the table.
     * 
     * @return self
     * 
     */
    public function from($spec, array $cols = [])
    {
        list($table, $alias) = $this->tableAlias($spec);
        
        // save in the sources list, overwriting previous values
        $this->addSource(
            'from',
            $alias,
            $table,
            null,
            null,
            $cols
        );
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds a sub-select and columns to the query.
     * 
     * The format is "FROM ($select) AS $alias"; an alias name is
     * always required so we can deconflict columns properly.
     * 
     * @param string|Select $spec If a Select
     * object, use as the sub-select; if a string, the sub-select
     * command string.
     * 
     * @param string $alias The alias name for the sub-select.
     * 
     * @param array $cols The columns to retrieve from the 
     * sub-select; by default, ['*'] (all columns).  This is unlike the
     * normal from() and join() methods, which by default select no
     * columns.
     * 
     * @return self
     * 
     */
    public function fromSubSelect($spec, $alias, array $cols = ['*'])
    {
        // convert to a string if needed
        if ($spec instanceof Select) {
            $spec = $spec->__toString();
        }
        
        // save in the sources list, overwriting previous values
        $this->addSource(
            'select',
            $alias,
            $spec,
            null,
            null,
            $cols
        );
        
        // done
        return $this;
    }
    
    /**
     * 
     * Adds a JOIN table and columns to the query.
     * 
     * @param string $type The join type: inner, left, natural, etc.
     * 
     * @param string $spec The table specification; "foo" or "foo AS bar".
     * 
     * @param string $cond Join on this condition.
     * 
     * @param array $cols The columns to select from the joined table.
     * 
     * @return self
     * 
     */
    public function join($type, $text, $cond, array $cols = [])
    {
        $this->addJoin($type, $text, $cond, $cols);
        return $this;
    }
    
    /**
     * 
     * Adds a JOIN to an aliased subselect and columns to the query.
     * 
     * @param string $type The join type: inner, left, natural, etc.
     * 
     * @param string|Select $spec If a Select
     * object, use as the sub-select; if a string, the sub-select
     * command string.
     * 
     * @param string $name The alias name for the sub-select.
     * 
     * @param string $cond Join on this condition.
     * 
     * @param array $cols The columns to select from the joined table.
     * 
     * @return self
     * 
     */
    public function joinSubSelect($type, $spec, $name, $cond, array $cols = [])
    {
        // convert to a string if needed
        if ($spec instanceof Select) {
            $spec = $spec->__toString();
        }
        
        $this->addJoin($type, "($spec) AS $name", $cond, $cols);
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
        if (empty($cond)) {
            return $this;
        }
        
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        if ($this->parts['where']) {
            $this->parts['where'][] = "AND $cond";
        } else {
            $this->parts['where'][] = $cond;
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
        if (empty($cond)) {
            return $this;
        }
        
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        if ($this->parts['where']) {
            $this->parts['where'][] = "OR $cond";
        } else {
            $this->parts['where'][] = $cond;
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
    public function groupBy(array $spec = [])
    {
        $spec = $this->sql->quoteName($spec);
        $this->parts['group_by'] = array_merge($this->parts['group_by'], $spec);
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
        if (empty($cond)) {
            return $this;
        }
        
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        if ($this->parts['having']) {
            $this->parts['having'][] = "AND $cond";
        } else {
            $this->parts['having'][] = $cond;
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
        if (empty($cond)) {
            return $this;
        }
        
        if ($val !== self::IGNORE) {
            $cond = $this->sql->quoteInto($cond, $val);
        }
        
        $cond = $this->sql->quoteNamesIn($cond);
        
        if ($this->parts['having']) {
            $this->parts['having'][] = "OR $cond";
        } else {
            $this->parts['having'][] = $cond;
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
    public function orderBy(array $spec = [])
    {
        if (empty($spec)) {
            return $this;
        }
        
        if (is_string($spec)) {
            $spec = explode(',', $spec);
        } else {
            settype($spec, 'array');
        }
        
        $spec = $this->sql->quoteNamesIn($spec);
        
        // force 'ASC' or 'DESC' on each order spec, default is ASC.
        foreach ($spec as $key => $val) {
            $asc  = (strtoupper(substr($val, -4)) == ' ASC');
            $desc = (strtoupper(substr($val, -5)) == ' DESC');
            if (! $asc && ! $desc) {
                $spec[$key] .= ' ASC';
            }
        }
        
        // merge them into the current order set
        $this->parts['order_by'] = array_merge($this->parts['order_by'], $spec);
        
        // done
        return $this;
    }
    
    /**
     * 
     * Sets a limit count and offset to the query.
     * 
     * @param int $count The number of rows to return.
     * 
     * @param int $offset Start returning after this many rows.
     * 
     * @return self
     * 
     */
    public function limit($count = null, $offset = null)
    {
        $this->parts['limit']['count']  = (int) $count;
        $this->parts['limit']['offset'] = (int) $offset;
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
    public function limitPage($page = null)
    {
        // reset the count and offset
        $this->parts['limit']['count']  = 0;
        $this->parts['limit']['offset'] = 0;
        
        // determine the count and offset from the page number
        $page = (int) $page;
        if ($page > 0) {
            $this->parts['limit']['count']  = $this->paging;
            $this->parts['limit']['offset'] = $this->paging * ($page - 1);
        }
        
        // done
        return $this;
    }
    
    /**
     * 
     * Takes the current select properties and prepares them for UNION with
     * the next set of select properties.
     * 
     * @return self
     * 
     */
    public function union()
    {
        $this->compound[] = $this->toString() . PHP_EOL
                          . 'UNION' . PHP_EOL;
        $this->reset();
        return $this;
    }
    
    /**
     * 
     * Takes the current select properties and prepares them for UNION ALL
     * with the next set of select properties.
     * 
     * @return self
     * 
     */
    public function unionAll()
    {
        $this->compound[] = $this->toString() . PHP_EOL
                          . 'UNION ALL' . PHP_EOL;
        $this->reset();
        return $this;
    }
    
    // -----------------------------------------------------------------
    // 
    // Support methods
    // 
    // -----------------------------------------------------------------
    
    /**
     * 
     * Clears the current select properties and row sources; generally used
     * after adding a compound.
     * 
     * @return void
     * 
     */
    protected function reset()
    {
        $this->parts = array(
            'distinct'  => false,
            'cols'      => [],
            'from'      => [],
            'join'      => [],
            'where'     => [],
            'group_by'  => [],
            'having'    => [],
            'order_by'  => [],
            'limit'     => ['count' => 0, 'offset' => 0],
        );
        
        $this->sources = [];
    }
    
    /**
     * 
     * Support method for building corrected parts from sources.
     * 
     * @param array $parts An array of SELECT parts.
     * 
     * @param array $sources An array of sources for the SELECT.
     * 
     * @return array An array of corrected SELECT parts.
     * 
     */
    protected function build($parts, $sources)
    {
        // build from scratch using the table and row sources.
        $parts['cols'] = [];
        $parts['from'] = [];
        $parts['join'] = [];
        
        // get a count of how many sources there are. if there's only 1, we
        // won't use column-name prefixes below. this will help soothe SQLite
        // on JOINs of sub-selects.
        // 
        // e.g., `JOIN (SELECT alias.col FROM tbl AS alias) ...`  won't work
        // right, SQLite needs `JOIN (SELECT col AS col FROM tbl AS alias)`.
        // 
        $count_sources = count($sources);
        
        // build from sources.
        foreach ($sources as $source) {
            
            // build the from and join parts.  note that we don't
            // build from 'cols' sources, since they are just named
            // columns without reference to a particular from or join.
            if ($source['type'] != 'cols') {
                $method = "build" . ucfirst($source['type']);
                $this->$method(
                    $parts,
                    $source['name'],
                    $source['orig'],
                    $source['join'],
                    $source['cond']
                );
            }
            
            // determine a prefix for the columns from this source
            if ($source['type'] == 'select' ||
                $source['name'] != $source['orig']) {
                // use the alias name, not the original name,
                // and where aliases are explicitly named.
                $prefix = $source['name'];
            } else {
                // use the original name
                $prefix = $source['orig'];
            }
            
            // add each of the columns from the source, deconflicting
            // along the way.
            foreach ($source['cols'] as $col) {
                
                // does it use a function?  we don't care if it's the first
                // char, since a paren in the first position means there's no
                // function name before it.
                $parens = strpos($col, '(');
                
                // choose our column-name deconfliction strategy.
                // catches any existing AS in the name.
                if ($parens) {
                    // if there are parens in the name, it's a function.
                    $tmp = $this->sql->quoteNamesIn($col);
                } elseif ($prefix == '' || $count_sources == 1) {
                    // if no prefix, that's a no-brainer.
                    // if there's only one source, deconfliction not needed.
                    $tmp = $this->sql->quoteName($col);
                } else {
                    // auto deconfliction.
                    $tmp = $this->sql->quoteName("$prefix.$col");
                }
                
                // force an "AS" if not already there, but only if the source
                // is not a manually-set column name, and the column is not a
                // literal star for all columns.
                if ($source['type'] != 'cols' && $col != '*') {
                    // force an AS if not already there. this is because
                    // sqlite returns col names as '"table"."col"' when there
                    // are 2 or more joins. so let's just standardize on
                    // always doing it.
                    // 
                    //  make sure there's no parens, or we get a bad col name
                    $pos = stripos($col, ' AS ');
                    if ($pos === false && ! $parens) {
                        $tmp .= " AS " . $this->sql->quoteName($col);
                    }
                }
                
                // add to the parts
                $parts['cols'][] = $tmp;
            }
        }
        
        // done!
        return $parts;
    }
    
    /**
     * 
     * Splits an identifier specification into a table name and an alias name.
     * 
     * Effectively splits the identifier at "AS", so that "foo AS bar"
     * becomes ['foo', 'bar'].
     * 
     * @param string $spec The identifier specification.
     * 
     * @return array An array where the first element is the table name and
     * the second element is the alias name.
     * 
     */
    protected function tableAlias($spec)
    {
        // does the spec have an "AS" alias? pick the right-most one near the
        // end of the string (note the "rr" in strripos).
        $pos = strripos($spec, ' AS ');
        if ($pos !== false) {
            $table = substr($spec, 0, $pos);
            $alias = substr($spec, $pos + 4);
        } else {
            $table = $spec;
            $alias = $spec;
        }
        return [$table, $alias];
    }
    
    /**
     * 
     * Support method for adding JOIN clauses.
     * 
     * @param string $type The type of join; empty for a plain JOIN, or
     * "LEFT", "INNER", "NATURAL", etc.
     * 
     * @param string $spec The table to join to.
     * 
     * @param string|array $cond Condiitons for the ON clause.
     * 
     * @param array|string $cols The columns to select from the
     * joined table.
     * 
     * @return self
     * 
     */
    protected function addJoin($type, $spec, $cond, $cols)
    {
        if ($type) {
            $type .= ' JOIN';
        } else {
            $type = 'JOIN';
        }
        
        // Add support for an array based $cond parameter
        if (is_array($cond)) {
            $on = [];
            foreach ((array) $cond as $key => $val) {
                if (is_int($key)) {
                    // integer key means a literal condition
                    // and no value to be quoted into it
                    $on[] = $val;
                } else {
                    // string $key means the key is a condition,
                    // and the $val should be quoted into it.
                    $on[] = $this->sql->quoteInto($key, $val);
                }
            }
            $cond = implode($on, ' AND ');
        }
        
        // convert to an array of table and alias
        list($table, $alias) = $this->tableAlias($spec);
        
        // save in the sources list, overwriting previous values
        $this->addSource(
            'join',
            $alias,
            $table,
            $type,
            $cond,
            $cols
        );
        
        return $this;
    }
    
    /**
     * 
     * Adds a row source (from table, from select, or join) to the 
     * sources array.
     * 
     * @param string $type The source type: 'from', 'join', or 'select'.
     * 
     * @param string $name The alias name.
     * 
     * @param string $orig The source origin, either a table name or a 
     * sub-select statement.
     * 
     * @param string $join If $type is 'join', the type of join ('left',
     * 'inner', etc.).
     * 
     * @param string $cond If $type is 'join', the join conditions.
     * 
     * @param array $cols The columns to select from the source.
     * 
     * @return void
     * 
     */
    protected function addSource($type, $name, $orig, $join, $cond, $cols)
    {
        if (is_string($cols)) {
            $cols = explode(',', $cols);
        }
        
        if ($cols) {
            settype($cols, 'array');
            foreach ($cols as $key => $val) {
                $cols[$key] = trim($val);
            }
        } else {
            $cols = [];
        }
        
        if ($type == 'cols') {
            $this->sources[] = array(
                'type' => $type,
                'name' => $name,
                'orig' => $orig,
                'join' => $join,
                'cond' => $cond,
                'cols' => $cols,
            );
        } else {
            $this->sources[$name] = array(
                'type' => $type,
                'name' => $name,
                'orig' => $orig,
                'join' => $join,
                'cond' => $cond,
                'cols' => $cols,
            );
        }
    }
    
    /**
     * 
     * Builds a part element **in place** using a 'from' source.
     * 
     * @param array &$parts The SELECT parts to build with.
     * 
     * @param string $alias The table alias.
     * 
     * @param string $table The real table name.
     * 
     * @return void
     * 
     */
    protected function buildFrom(&$parts, $alias, $table)
    {
        if ($alias == $table) {
            $parts['from'][] = $this->sql->quoteName($alias);
        } else {
            $parts['from'][] = $this->sql->quoteName($table)
                             . ' '
                             . $this->sql->quoteName($alias);
        }
    }
    
    /**
     * 
     * Builds a part element **in place** using a 'join' source.
     * 
     * @param array &$parts The SELECT parts to build with.
     * 
     * @param string $name The table alias.
     * 
     * @param string $orig The original table name.
     * 
     * @param string $join The join type (null, 'left', 'inner', etc).
     * 
     * @param string $cond Join conditions.
     * 
     * @return void
     * 
     */
    protected function buildJoin(&$parts, $name, $orig, $join, $cond)
    {
        $tmp = array(
            'type' => $join,
            'name' => null,
            'cond' => $this->sql->quoteNamesIn($cond),
        );
        
        if ($name == $orig) {
            $tmp['name'] = $this->sql->quoteName($name);
        } elseif ($orig[0] == '(') {
            $tmp['name'] = $orig
                         . ' '
                         . $this->sql->quoteName($name);
        } else {
            $tmp['name'] = $this->sql->quoteName($orig)
                         . ' '
                         . $this->sql->quoteName($name);
        }
        
        $parts['join'][] = $tmp;
    }
    
    /**
     * 
     * Builds a part element **in place** using a 'select' source.
     * 
     * @param array &$parts The SELECT parts to build with.
     * 
     * @param string $name The subselect alias.
     * 
     * @param string $orig The subselect command string.
     * 
     * @return void
     * 
     */
    protected function buildSelect(&$parts, $name, $orig)
    {
        $parts['from'][] = "($orig) " . $this->sql->quoteName($name);
    }

    /**
     * 
     * Builds a single SELECT command string from its component parts.
     * 
     * @return string A SELECT command string.
     * 
     */
    protected function toString()
    {
        $parts = $this->build($this->parts, $this->sources);
        
        // newline and indent
        $line = PHP_EOL . '    ';
        
        // comma separator, newline, and indent
        $csep = ',' . $line;
        
        // the text of the select string
        $text = 'SELECT ';
        
        // add distinct
        if ($parts['distinct']) {
            $text .= 'DISTINCT ';
        }
        
        // add columns
        $text .= $line . implode($csep, $parts['cols']) . PHP_EOL;
        
        // from these tables
        if ($parts['from']) {
            $text .= 'FROM' . $line;
            $text .= implode($csep, $parts['from']) . PHP_EOL;
        }
        
        // join these tables
        foreach ($parts['join'] as $join) {
            $text .= "{$join['type']} {$join['name']} ON {$join['cond']}" . PHP_EOL;
        }
        
        // where these conditions
        if ($parts['where']) {
            $text .= 'WHERE' . $line;
            $text .= implode($line, $parts['where']) . PHP_EOL;
        }
        
        // grouped by these columns
        if ($parts['group_by']) {
            $text .= 'GROUP BY' . $line;
            $text .= implode($csep, $parts['group_by']) . PHP_EOL;
        }
        
        // having these conditions
        if ($parts['having']) {
            $text .= 'HAVING' . $line;
            $text .= implode($line, $parts['having']) . PHP_EOL;
        }
        
        // ordered by these columns
        if ($parts['order_by']) {
            $text .= 'ORDER BY' . $line;
            $text .= implode($csep, $parts['order_by']) . PHP_EOL;
        }
        
        // mpodify with a limit clause per the adapter
        $count  = $parts['limit']['count'];
        $offset = $parts['limit']['offset'];
        $this->sql->limit($text, $count, $offset);
        
        // done!
        return $text;
    }
}
