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
namespace Aura\Sql\Query\Mysql;

/**
 *
 * An object for MySQL UPDATE queries.
 *
 * @package Aura.Sql
 *
 */
class Update extends \Aura\Sql\Query\Update
{
    /**
     *
     * The number of rows to update
     *
     * @var int
     *
     */
    protected $limit = 0;

    /**
     * @return string
     */
    public function __toString()
    {
        $sql = parent::__toString();

        // modify with a limit clause per the connection
        $this->connection->limit($sql, $this->limit);

        return $sql;
    }

    /**
     *
     * Sets a limit count on the query.
     *
     * @param int $limit The number of rows to update.
     *
     * @return $this
     *
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }
}