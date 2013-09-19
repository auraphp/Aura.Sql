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

class PdoFactory
{
    protected $attributes = array(
        'mysql' => array(
            Pdo::ATTR_QUOTE_NAME_PREFIX => '`',
            Pdo::ATTR_QUOTE_NAME_SUFFIX => '`',
        ),
        'pgsql' => array(
            Pdo::ATTR_QUOTE_NAME_PREFIX => '"',
            Pdo::ATTR_QUOTE_NAME_SUFFIX => '"',
        ),
        'sqlite' => array(
            Pdo::ATTR_QUOTE_NAME_PREFIX => '"',
            Pdo::ATTR_QUOTE_NAME_SUFFIX => '"',
        ),
        'sqlsrv' => array(
            Pdo::ATTR_QUOTE_NAME_PREFIX => '[',
            Pdo::ATTR_QUOTE_NAME_SUFFIX => ']',
        ),
        
    );
    
    public function __construct(array $attributes = array())
    {
        $this->attributes = array_merge_recursive(
            $this->attributes,
            $attributes
        );
    }
    
    public function newInstance(
        $dsn,
        $username = null,
        $password = null,
        array $options = null,
        array $attributes = null
    ) {
        $pos = strpos($dsn, ':');
        $type = substr($dsn, 0, $pos);
        if (isset($this->attributes[$type])) {
            $attributes = array_merge($this->attributes[$type], (array) $attributes);
        }
        return new Pdo($dsn, $username, $password, $options, $attributes);
    }
}
