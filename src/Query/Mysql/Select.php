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
 * An object for MySQL SELECT queries.
 *
 * @package Aura.Sql
 *
 */
class Select extends \Aura\Sql\Query\Select
{
    const FLAG_HIGH_PRIORITY = 'HIGH_PRIORITY';
    const FLAG_STRAIGHT_JOIN = 'STRAIGHT_JOIN';
    const FLAG_SQL_CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';
    const FLAG_SQL_CACHE = 'SQL_CACHE';
    const FLAG_SQL_NO_CACHE = 'SQL_NO_CACHE';
    const FLAG_SQL_SMALL_RESULT = 'SQL_SMALL_RESULT';
    const FLAG_SQL_BIG_RESULT = 'SQL_BIG_RESULT';
    const FLAG_SQL_BUFFER_RESULT = 'SQL_BUFFER_RESULT';

    /**
     *
     * Adds or removes SQL_CALC_FOUND_ROWS flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function calcFoundRows($enable = true)
    {
        $this->setFlag(self::FLAG_SQL_CALC_FOUND_ROWS, $enable);

        return $this;
    }

    /**
     *
     * Adds or removes SQL_CACHE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function cache($enable = true)
    {
        $this->setFlag(self::FLAG_SQL_CACHE, $enable);

        return $this;
    }

    /**
     *
     * Adds or removes SQL_NO_CACHE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function noCache($enable = true)
    {
        $this->setFlag(self::FLAG_SQL_NO_CACHE, $enable);

        return $this;
    }

    /**
     *
     * Adds or removes STRAIGHT_JOIN flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function straightJoin($enable = true)
    {
        $this->setFlag(self::FLAG_STRAIGHT_JOIN, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes HIGH_PRIORITY flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function highPriority($enable = true)
    {
        $this->setFlag(self::FLAG_HIGH_PRIORITY, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes SQL_SMALL_RESULT flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function smallResult($enable = true)
    {
        $this->setFlag(self::FLAG_SQL_SMALL_RESULT, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes SQL_BIG_RESULT flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function bigResult($enable = true)
    {
        $this->setFlag(self::FLAG_SQL_BIG_RESULT, $enable);
        return $this;
    }

    /**
     *
     * Adds or removes SQL_BUFFER_RESULT flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function bufferResult($enable = true)
    {
        $this->setFlag(self::FLAG_SQL_BUFFER_RESULT, $enable);
        return $this;
    }
}
