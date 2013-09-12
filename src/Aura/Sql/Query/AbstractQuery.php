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
     * An SQL connection.
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
     * @var string
     */
    protected $stringNewLine;

    /**
     * @var string
     */
    protected $stringCommaSeparator;
    
    /**
     * @var string
     */
    protected $stringIndent;

    /**
     * Constructor.
     * 
     * @param AbstractConnection $connection An SQL connection.
     */
    public function __construct(AbstractConnection $connection)
    {
        $this->stringIndent = '    ';
        $this->stringNewLine = PHP_EOL . $this->stringIndent;
        $this->stringCommaSeparator = ',' . $this->stringNewLine;
        
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

    /**
     * 
     * Gets the database connection for this query object.
     * 
     * @return AbstractConnection
     * 
     */
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
        return $this->stringNewLine . implode($this->stringCommaSeparator, $list) . PHP_EOL;
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
        return $this->stringNewLine . implode($this->stringNewLine, $list) . PHP_EOL;
    }

    /**
     * 
     * Sets values to bind into the query; this overrides any previous
     * bindings.
     * 
     * @param array $bind Values to bind to the query.
     * 
     * @return void
     * 
     */
    public function setBind(array $bind)
    {
        $this->bind = $bind;
    }

    /**
     * 
     * Adds values to bind into the query; merges with existing values.
     * 
     * @param array $bind Values to bind to the query.
     * 
     * @return void
     * 
     */
    public function addBind(array $bind)
    {
        $this->bind = array_merge($this->bind, $bind);
    }

    /**
     * 
     * Gets the values to bind into the query.
     * 
     * @return array
     * 
     */
    public function getBind()
    {
        return $this->bind;
    }
}
