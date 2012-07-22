<?php
namespace Aura\Sql\Query;

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
    public function where($cond)
    {
        $cond = $this->sql->quoteNamesIn($cond);
        
        if (func_num_args() > 1) {
            $cond = $this->sql->quoteInto($cond, func_get_arg(1));
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
    public function orWhere($cond)
    {
        $cond = $this->sql->quoteNamesIn($cond);

        if (func_num_args() > 1) {
            $cond = $this->sql->quoteInto($cond, func_get_arg(1));
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
