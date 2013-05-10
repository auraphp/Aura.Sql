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
 * An object for DELETE queries.
 * 
 * @package Aura.Sql
 * 
 */
class Delete extends AbstractQuery
{
    use WhereTrait;

    /**
     * 
     * The table to delete from.
     * 
     * @var string
     * 
     */
    protected $table;

    /**
     * 
     * Returns this object as an SQL statement string.
     * 
     * @return string An SQL statement string.
     * 
     */
    public function __toString()
    {
        $where = null;
        if ($this->where) {
            $where .= PHP_EOL . 'WHERE' . $this->indent($this->where);
        }

        return 'DELETE FROM ' . $this->table . $where;
    }

    /**
     * 
     * Sets the table to delete from.
     * 
     * @param string $table The table to delete from.
     * 
     * @return $this
     * 
     */
    public function from($table)
    {
        $this->table = $this->connection->quoteName($table);
        return $this;
    }
}
