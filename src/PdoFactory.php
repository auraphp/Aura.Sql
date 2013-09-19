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
 * A factory to create PDO instances.
 * 
 * @package Aura.Sql
 * 
 */
class PdoFactory
{
    /**
     * 
     * Attributes for each driver type.
     * 
     * @var array
     * 
     */
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
    
    /**
     * 
     * Constructor.
     * 
     * @param array $attributes Attribute overrides for each driver type.
     * 
     */
    public function __construct(array $attributes = array())
    {
        $this->attributes = array_merge_recursive(
            $this->attributes,
            $attributes
        );
    }
    
    /**
     * 
     * Returns a new PDO instance.
     * 
     * @param string $dsn The data source name for the instance.
     * 
     * @param string $username The username for the instance.
     * 
     * @param string $password The password for the instance.
     * 
     * @param array $options Driver-specific options.
     * 
     * @param array $attributes Attributes to set after instance.
     * 
     * @see http://php.net/manual/en/pdo.construct.php
     * 
     */
    public function newInstance(
        $dsn,
        $username = null,
        $password = null,
        array $options = null,
        array $attributes = null
    ) {
        // get the driver type from the dsn
        $pos = strpos($dsn, ':');
        $type = substr($dsn, 0, $pos);
        
        // do we have attributes for the driver type?
        if (isset($this->attributes[$type])) {
            // yes, merge them in
            $attributes = array_merge(
                $this->attributes[$type],
                (array) $attributes
            );
        }
        
        // create and return the PDO instance
        return new Pdo($dsn, $username, $password, $options, $attributes);
    }
}
