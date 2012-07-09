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

use Aura\Sql\Adapter\AbstractAdapter;

/**
 * 
 * Create Select objects.
 * 
 * @package Aura.Sql
 * 
 */
class SelectFactory
{
    /**
     * 
     * Returns a new Select object.
     * 
     * @param AbstractAdapter $sql The SQL connection adapter.
     * 
     * @return Select
     * 
     */
    public function newInstance(AbstractAdapter $sql)
    {
        return new Select($sql);
    }
}
