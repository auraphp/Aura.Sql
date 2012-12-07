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
 * An object for UPDATE queries.
 * 
 * @package Aura.Sql
 * 
 */
class Update extends AbstractQuery
{
    use ValuesTrait;
    use WhereTrait;

    /**
     * 
     * The table to update.
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
        $values = [];
        foreach ($this->values as $col => $value) {
            $values[] = "{$col} = {$value}";
        }

        $where = null;
        if ($this->where) {
            $where .= 'WHERE' . $this->indent($this->where);
        }

        return 'UPDATE ' . $this->table . PHP_EOL
             . 'SET' . $this->indentCsv($values)
             . $where;
    }

    /**
     * 
     * Sets the table to update.
     * 
     * @param string $table The table to update.
     * 
     * @return $this
     * 
     */
    public function table($table)
    {
        $this->table = $this->connection->quoteName($table);
        return $this;
    }
}
