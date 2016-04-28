<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

class ColStatementIterator extends StatementIterator
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
     * Returns value at current position.
     *
     * @return mixed
     *
     */
    public function current()
    {
        return $this->rowData[0];
    }
}
