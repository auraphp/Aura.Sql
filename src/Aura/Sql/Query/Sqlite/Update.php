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
namespace Aura\Sql\Query\Sqlite;

use Aura\Sql\Query\LimitTrait;
use Aura\Sql\Query\OffsetTrait;
use Aura\Sql\Query\OrderByTrait;

/**
 *
 * An object for MySQL UPDATE queries.
 *
 * @package Aura.Sql
 *
 */
class Update extends \Aura\Sql\Query\Update
{
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    
    const FLAG_OR_ABORT = 'OR ABORT';
    const FLAG_OR_FAIL = 'OR FAIL';
    const FLAG_OR_IGNORE = 'OR IGNORE';
    const FLAG_OR_REPLACE = 'OR REPLACE';
    const FLAG_OR_ROLLBACK = 'OR ROLLBACK';

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
        
        $sql .= $this->getOrderByStatement();
        
        $this->connection->limit($sql, $this->limit, $this->offset);
        
        return $sql;
    }

    /**
     *
     * Adds or removes OR ABORT flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orAbort($enable = true)
    {
        $this->setFlag(self::FLAG_OR_ABORT, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR FAIL flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orFail($enable = true)
    {
        $this->setFlag(self::FLAG_OR_FAIL, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR IGNORE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orIgnore($enable = true)
    {
        $this->setFlag(self::FLAG_OR_IGNORE, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR REPLACE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orReplace($enable = true)
    {
        $this->setFlag(self::FLAG_OR_REPLACE, $enable);
        return $this;
    }
    
    /**
     *
     * Adds or removes OR ROLLBACK flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orRollback($enable = true)
    {
        $this->setFlag(self::FLAG_OR_ROLLBACK, $enable);
        return $this;
    }
}
