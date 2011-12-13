<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql\Connection;

/**
 * 
 * Sql srv Denali adapter
 * 
 * @package Aura.Sql
 * 
 */
use Aura\Sql\Select;
class SqlsrvDenali extends Sqlsrv
{
    // **MUST** have an ORDER clause to work;
    // in Denali, OFFSET is a sub-clause of the ORDER clause.
    // also, cannot use FETCH without OFFSET.
    protected function convertSelectStrategy(Select $select)
    {
        return $select->__toString() . "\n"
             . "OFFSET {$select->offset} ROWS\n"
             . "FETCH NEXT {$select->limit} ROWS ONLY"; 
    }
}
