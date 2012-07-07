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
 * A factory to create Select objects
 * 
 * @package Aura.Sql
 * 
 */
class SelectFactory
{
    /**
     * 
     * Create a Select object
     * 
     * @param AbstractAdapter $sql An AbstractAdapter class
     * 
     * @return Select a new select object
     * 
     */
    public function newInstance(AbstractAdapter $sql)
    {
        return new Select($sql);
    }
}
