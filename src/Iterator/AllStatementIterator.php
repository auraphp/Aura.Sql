<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

class AllStatementIterator extends StatementIterator
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
        parent::__construct($statement, \PDO::FETCH_ASSOC);
    }
}
