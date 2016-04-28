<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

class ObjectsStatementIterator extends StatementIterator
{
    /**
     *
     * Creates new iterator.
     *
     * @param \PDOStatement $statement PDO statement.
     *
     * @param string $class_name The name of the class to create.
     *
     * @param array $ctor_args Arguments to pass to the object constructor.
     *
     */
    public function __construct(
        \PDOStatement $statement,
        $class_name = 'StdClass',
        array $ctor_args = array()
    ) {
        if ($ctor_args) {
            $statement->setFetchMode(
                \PDO::FETCH_CLASS,
                $class_name,
                $ctor_args
            );
        } else {
            $statement->setFetchMode(\PDO::FETCH_CLASS, $class_name);
        }

        parent::__construct($statement, \PDO::FETCH_CLASS);
    }
}
