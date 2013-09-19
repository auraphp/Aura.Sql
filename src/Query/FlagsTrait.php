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
     * 
     * Returns the flags as a space-separated string.
     *
     * @return string
     * 
     */
    protected function getFlagsAsString()
    {
        if ($this->flags) {
            return ' ' . implode(' ', array_keys($this->flags));
        } else {
            return '';
        }
    }

    /**
     * 
     * Sets or unsets specified flag.
     *
     * @param string $flag Flag to set or unset
     * 
     * @param bool $enable Flag status - enabled or not (default true)
     * 
     * @return void
     * 
     */
    protected function setFlag($flag, $enable = true)
    {
        if ($enable) {
            $this->flags[$flag] = true;
        } else {
            unset($this->flags[$flag]);
        }
    }

    /**
     * 
     * Reset all query flags.
     * 
     * @return void
     * 
     */
    protected function resetFlags()
    {
        $this->flags = [];
    }
}
