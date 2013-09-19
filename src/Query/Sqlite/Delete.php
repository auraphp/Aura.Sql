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
 * An object for Sqlite DELETE queries.
 *
 * @package Aura.Sql
 *
 */
class Delete extends \Aura\Sql\Query\Delete
{
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    
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
        
        $sql .= $this->getOrderByClause();
        
        $this->connection->limit($sql, $this->limit, $this->offset);
        
        return $sql;
    }
}
