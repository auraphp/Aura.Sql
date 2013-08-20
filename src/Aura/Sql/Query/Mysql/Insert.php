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
class Insert extends \Aura\Sql\Query\Insert
{
    const FLAG_DELAYED = 'DELAYED';
    const FLAG_IGNORE = 'IGNORE';
    const FLAG_HIGH_PRIORITY = 'HIGH_PRIORITY';
    const FLAG_LOW_PRIORITY = 'LOW_PRIORITY';

    /**
     *
     * Adds or removes HIGH_PRIORITY flag.
     *
     * @param bool $flag Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function highPriority($flag = true)
    {
        $this->setFlag(self::FLAG_HIGH_PRIORITY, $flag);
        return $this;
    }

    /**
     *
     * Adds or removes LOW_PRIORITY flag.
     *
     * @param bool $flag Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function lowPriority($flag = true)
    {
        $this->setFlag(self::FLAG_LOW_PRIORITY, $flag);
        return $this;
    }

    /**
     *
     * Adds or removes IGNORE flag.
     *
     * @param bool $flag Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function ignore($flag = true)
    {
        $this->setFlag(self::FLAG_IGNORE, $flag);
        return $this;
    }

    /**
     *
     * Adds or removes DELAYED flag.
     *
     * @param bool $flag Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function delayed($flag = true)
    {
        $this->setFlag(self::FLAG_DELAYED, $flag);
        return $this;
    }
}
