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
 * A trait for adding query flags (such as DISTINCT in SELECT).
 *
 * @package Aura.Sql
 *
 */
trait FlagsTrait
{
    /**
     *
     * The list of flags.
     *
     * @var array
     *
     */
    protected $flags = [];

    /**
     * Returns flags string for query.
     *
     * @return string
     */
    protected function getFlagsString()
    {
        if (count($this->flags) > 0) {
            return ' ' . implode(' ', $this->flags);
        } else {
            return '';
        }
    }

    /**
     * Sets or unsets specified flag.
     *
     * @param string $flag Flag to set or unset
     * @param bool $enable Flag status - enabled or not (default true)
     */
    protected function setFlag($flag, $enable = true)
    {
        $flagKey = array_search($flag, $this->flags);
        $hasFlag = $flagKey !== false;

        if ($enable) {
            if (!$hasFlag) {
                $this->flags[] = $flag;
            }
        } elseif ($hasFlag) {
            unset($this->flags[$flagKey]);
        }
    }

    /**
     * Reset all query flags.
     */
    protected function resetFlags()
    {
        $this->flags = [];
    }
}
