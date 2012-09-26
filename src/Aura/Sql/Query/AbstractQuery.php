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
    protected $sql;

    /**
     * 
     * Data to be bound to the query.
     * 
     * @var array
     * 
     */
    protected $data = [];

    /**
     * 
     * Constructor.
     * 
     * @param AbstractConnection $sql An SQL connection.
     * 
     * @return void
     * 
     */
    public function __construct(AbstractConnection $sql)
    {
        $this->sql = $sql;
    }
    
    /**
     * 
     * Converts this query object to a string.
     * 
     * @return string
     * 
     */
    abstract public function __toString();
    
    public function getSql()
    {
        return $this->sql;
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
    
    public function setData(array $data)
    {
        $this->data = $data;
    }
    
    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }
    
    public function getData()
    {
        return $this->data;
    }
}
