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

use Aura\Sql\Query\LimitTrait;

/**
 *
 * An object for MySQL UPDATE queries.
 *
 * @package Aura.Sql
 *
 */
class Delete extends \Aura\Sql\Query\Delete
{
    use LimitTrait;
    
    const FLAG_IGNORE = 'IGNORE';
    const FLAG_QUICK = 'QUICK';
    const FLAG_LOW_PRIORITY = 'LOW_PRIORITY';

    /**
     * 
     * Converts this query object to a string.
     * 
     * @return string
     * 
     */
    public function __toString()
    {
        $sql = parent::__toString();
        $this->connection->limit($sql, $this->limit);
        return $sql;
    }

    /**
     *
     * Adds or removes LOW_PRIORITY flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function lowPriority($enable = true)
    {
        $this->setFlag(self::FLAG_LOW_PRIORITY, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes IGNORE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function ignore($enable = true)
    {
        $this->setFlag(self::FLAG_IGNORE, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes QUICK flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function quick($enable = true)
    {
        $this->setFlag(self::FLAG_QUICK, $enable);
        return $this;
    }
}
