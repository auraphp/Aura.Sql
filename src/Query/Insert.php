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
 * An object for INSERT queries.
 *
 * @package Aura.Sql
 *
 */
class Insert extends AbstractQuery
{
    use ValuesTrait;
    use FlagsTrait;

    /**
     *
     * The table to insert into.
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
        return 'INSERT' . $this->getFlagsAsString() . ' INTO ' . $this->table . ' ('
             . $this->indentCsv(array_keys($this->values))
             . ') VALUES ('
             . $this->indentCsv(array_values($this->values))
             . ')' . PHP_EOL;
    }

    /**
     *
     * Sets the table to insert into.
     *
     * @param string $table The table to insert into.
     *
     * @return $this
     *
     */
    public function into($table)
    {
        $this->table = $this->connection->quoteName($table);
        return $this;
    }
}
