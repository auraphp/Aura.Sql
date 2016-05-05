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

/**
 *
 * The iterator equivalent of `fetchPairs()`.
 *
 * @package Aura.Sql
 *
 */
class PairsIterator extends AbstractIterator
{
    /**
     * The function to be apply on all element
     *
     * @var callable
     */
    protected $callable;

    /**
     *
     * Constructor.
     *
     * @param PDOStatement $statement PDO statement.
     *
     */
    public function __construct(PDOStatement $statement, $callable = null)
    {
        $this->statement = $statement;
        $this->statement->setFetchMode(PDO::FETCH_NUM);
        if (! $callable) {
            $callable = function (array $row) {
                return $row;
            };
        }
        $this->callable = $callable;
    }

    /**
     *
     * Fetches next row from statement.
     *
     */
    public function next()
    {
        $this->key = false;
        $this->row = $this->statement->fetch();
        if ($this->row !== false) {
            $this->row = call_user_func($this->callable, $this->row);
            $this->key = $this->row[0];
            $this->row = $this->row[1];
        }
    }
}
