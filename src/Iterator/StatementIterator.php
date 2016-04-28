<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

class StatementIterator implements \Iterator
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
     * Fetch style.
     *
     * @var integer
     *
     */
    protected $fetchStyle;

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
     * Flag indicating there's a valid resource or not.
     *
     * @var boolean
     *
     */
    protected $isValid = false;

    /**
     *
     * Creates new iterator.
     *
     * @param \PDOStatement $statement PDO statement.
     *
     * @param integer $fetch_style Fetch style.
     *
     */
    public function __construct(\PDOStatement $statement, $fetch_style = null)
    {
        $this->statement = $statement;

        if (isset($fetch_style)) {
            $this->fetchStyle = $fetch_style;
        } else {
            $this->fetchStyle = \PDO::ATTR_DEFAULT_FETCH_MODE;
        }
    }

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
        $this->position++;
        $this->fetch();
        $this->isValid = $this->rowData !== false;
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
        return $this->isValid;
    }

    /**
     *
     * Fetches data for next row.
     *
     * @return void
     *
     */
    protected function fetch()
    {
        $this->rowData = $this->statement->fetch($this->fetchStyle);
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
