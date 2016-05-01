<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

use Iterator;

abstract class AbstractIterator implements Iterator
{
    /**
     *
     * Current index in recordset.
     *
     * @var integer
     *
     */
    protected $position = -1;

    /**
     *
     * PDO statement.
     *
     * @var \PDOStatement
     *
     */
    protected $statement;

    /**
     *
     * Data in current row of recordset.
     *
     * @var array
     *
     */
    protected $rowData = array();

    /**
     *
     * Moves recordset pointer to first element.
     *
     * @return void
     *
     */
    public function rewind()
    {
        $this->position = -1;
        $this->next();
    }

    /**
     *
     * Returns value at current position.
     *
     * @return mixed
     *
     */
    public function current()
    {
        return $this->rowData;
    }

    /**
     *
     * Returns key at current position.
     *
     * @return mixed
     *
     */
    public function key()
    {
        return $this->position;
    }

    /**
     *
     * Moves recordset pointer to next position.
     *
     * @return void
     *
     */
    public function next()
    {
        $this->position ++;
        $this->rowData = $this->statement->fetch();
    }

    /**
     *
     * Detects if current position is within recordset bounds.
     *
     * @return boolean
     *
     */
    public function valid()
    {
        return $this->rowData !== false;
    }

    /**
     *
     * Closes recordset and frees memory.
     *
     * @return void
     *
     */
    public function close()
    {
        $this->statement->closeCursor();
        unset($this->statement);
    }

    /**
     *
     * Frees memory when object is destroyed.
     *
     * @return void
     *
     */
    public function __destruct()
    {
        $this->close();
    }
}
