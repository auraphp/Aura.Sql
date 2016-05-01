<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql\Iterator;

use PDO;
use PDOStatement;

class ObjectsStatementIterator extends AbstractIterator
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
        PDOStatement $statement,
        $class_name = 'StdClass',
        array $ctor_args = array()
    ) {
        $this->statement = $statement;
        if ($ctor_args) {
            $this->statement->setFetchMode(
                PDO::FETCH_CLASS,
                $class_name,
                $ctor_args
            );
        } else {
            $this->statement->setFetchMode(PDO::FETCH_CLASS, $class_name);
        }
    }
}
