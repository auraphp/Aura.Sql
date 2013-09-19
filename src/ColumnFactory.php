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
namespace Aura\Sql;

/**
 * 
 * A factory for column objects.
 * 
 * @package Aura.Sql
 * 
 */
class ColumnFactory
{
    /**
     * 
     * Returns a new Column object.
     * 
     * @param string $name The name of the column.
     * 
     * @param string $type The datatype of the column.
     * 
     * @param int $size The size of the column.
     * 
     * @param int $scale The scale of the column (i.e., the number of digits
     * after the decimal point).
     * 
     * @param bool $notnull Is the column defined as NOT NULL (i.e.,
     * required) ?
     * 
     * @param mixed $default The default value of the column.
     * 
     * @param bool $autoinc Is the column auto-incremented?
     * 
     * @param bool $primary Is the column part of the primary key?
     * 
     */
    public function newInstance(
        $name,
        $type,
        $size,
        $scale,
        $notnull,
        $default,
        $autoinc,
        $primary
    ) {
        return new Column(
            $name,
            $type,
            $size,
            $scale,
            $notnull,
            $default,
            $autoinc,
            $primary
        );
    }
}
