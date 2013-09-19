<?php
/**
 * 
 * This file is part of Aura for PHP.
 * 
 * @package Aura.Sql
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Sql;

/**
 * 
 * Locates PDO services for default, read, and write databases.
 * 
 * @package Aura.Sql
 * 
 */
interface PdoLocatorInterface
{
    public function setDefault($spec);
    public function getDefault();
    public function setRead($name, $spec);
    public function getRead($name = null);
    public function setWrite($name, $spec);
    public function getWrite($name = null);
}
