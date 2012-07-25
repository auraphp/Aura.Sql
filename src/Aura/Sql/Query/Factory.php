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

use Aura\Sql\Adapter\AbstractAdapter;

/**
 * 
 * Creates query statement objects.
 * 
 * @package Aura.Sql
 * 
 */
class Factory
{
    /**
     * 
     * Returns a new query object.
     * 
     * @param string $type The query object type.
     * 
     * @param AbstractAdapter $sql The SQL connection adapter.
     * 
     * @return AbstractQuery
     * 
     */
    public function newInstance($type, AbstractAdapter $sql)
    {
        $class = '\Aura\Sql\Query\\' . ucfirst($type);
        return new $class($sql);
    }
}
