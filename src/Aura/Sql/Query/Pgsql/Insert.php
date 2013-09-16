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
namespace Aura\Sql\Query\Pgsql;

use Aura\Sql\Query\ReturningTrait;

/**
 *
 * An object for PgSQL INSERT queries.
 *
 * @package Aura.Sql
 *
 */
class Insert extends \Aura\Sql\Query\Insert
{
    use ReturningTrait;
    
    /**
     * 
     * Converts this query object to a string.
     * 
     * @return string
     * 
     */
    public function __toString()
    {
        return parent::__toString() . $this->getReturningClause();
    }
}
