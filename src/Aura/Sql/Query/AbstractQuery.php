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

use Aura\Sql\Connection\AbstractConnection;

/**
 * 
 * Abstract query object for Select, Insert, Update, and Delete.
 * 
 * @package Aura.Sql
 * 
 */
abstract class AbstractQuery
{
    /**
     * 
     * An SQL connection connection.
     * 
     * @var AbstractConnection
     * 
     */
    protected $connection;

    /**
     * 
     * Data to be bound to the query.
     * 
     * @var array
     * 
     */
    protected $bind = [];

    /**
     * 
     * Constructor.
     * 
     * @param AbstractConnection $connection An SQL connection.
     * 
     * @return void
     * 
     */
    public function __construct(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * 
     * Converts this query object to a string.
     * 
     * @return string
     * 
     */
    abstract public function __toString();
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * 
     * Returns an array as an indented comma-separated values string.
     * 
     * @param array $list The values to convert.
     * 
     * @return string
     * 
     */
    protected function indentCsv(array $list)
    {
        return PHP_EOL
             . '    ' . implode(',' . PHP_EOL . '    ', $list)
             . PHP_EOL;
    }
    
    /**
     * 
     * Returns an array as an indented string.
     * 
     * @param array $list The values to convert.
     * 
     * @return string
     * 
     */
    protected function indent($list)
    {
        return PHP_EOL
             . '    ' . implode(PHP_EOL . '    ', $list)
             . PHP_EOL;
    }
    
    public function setBind(array $bind)
    {
        $this->data = $bind;
    }
    
    public function addBind(array $bind)
    {
        $this->data = array_merge($this->data, $bind);
    }
    
    public function getBind()
    {
        return $this->data;
    }
}
