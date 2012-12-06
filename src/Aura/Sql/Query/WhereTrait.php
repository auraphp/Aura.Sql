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
 * A trait for adding WHERE conditions.
 * 
 * @package Aura.Sql
 * 
 */
trait WhereTrait
{
    /**
     * 
     * The list of WHERE conditions.
     * 
     * @var array
     * 
     */
    protected $where = [];

    /**
     * 
     * Adds a WHERE condition to the query by AND; if a value is passed as the
     * second param, it will be quoted and replaced into the condition 
     * wherever a question-mark appears.
     * 
     * Array values are quoted and comma-separated.
     * 
     * @param string $cond The WHERE condition.
     * 
     * @return $this
     * 
     */
    public function where($cond)
    {
        $cond = $this->connection->quoteNamesIn($cond);

        if (func_num_args() > 1) {
            $cond = $this->connection->quoteInto($cond, func_get_arg(1));
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
     * Adds a WHERE condition to the query by OR; otherwise identical to 
     * `where()`.
     * 
     * @param string $cond The WHERE condition.
     * 
     * @return $this
     * 
     * @see where()
     * 
     */
    public function orWhere($cond)
    {
        $cond = $this->connection->quoteNamesIn($cond);

        if (func_num_args() > 1) {
            $cond = $this->connection->quoteInto($cond, func_get_arg(1));
        }

        if ($this->where) {
            $this->where[] = "OR $cond";
        } else {
            $this->where[] = $cond;
        }

        // done
        return $this;
    }
}
