<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

class PairsStatementIterator extends StatementIterator
{
    /**
     *
     * Creates new iterator.
     *
     * @param \PDOStatement $statement PDO statement.
     *
     */
    public function __construct(\PDOStatement $statement)
    {
        parent::__construct($statement, \PDO::FETCH_NUM);
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
        return $this->rowData[0];
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
        return $this->rowData[1];
    }
}
