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
 * Create query statement objects.
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
     * @param AbstractAdapter $sql The SQL connection adapter.
     * 
     * @return AbstractQuery
     * 
     */
    public function newInstance($type, AbstractAdapter $sql)
    {
        switch (strtolower($type)) {
            case 'select':
                return new Select($sql);
                break;
            case 'insert':
                return new Insert($sql);
                break;
            case 'update':
                return new Update($sql);
                break;
            case 'delete':
                return new Delete($sql);
                break;
        }
    }
}
