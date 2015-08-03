<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql;

/**
 *
 * This support class for ExtendedPdo rebuilds an SQL statement for automatic
 * binding of values. Specialized for PostgreSQL databases
 *
 * @package Aura.Sql
 *
 */
class PostgresRebuilder implements \Aura\Sql\RebuilderInterface
{
    /**
     * @var Rebuilder
     */
    private $base_rebuilder;

    /**
     * Constructor. Instiate a basic rebuilder and register callbacks specific to PostgreSQL syntax
     */
    public function __construct()
    {
        $this->base_rebuilder = new Rebuilder();
        $this->base_rebuilder->registerStatementPartsHandler('$', array($this, 'handleDollarCharacter'));
    }

    /**
     *
     * Rebuilds a statement with array values put in individual values and parameters. Multiple occurrences of the same
     * placeholder are also replaced.
     *
     * @param string $statement The statement to rebuild.
     *
     * @param array $values The values to bind and/or replace into a statement.
     *
     * @param string $charset The character set the statement is using.
     *
     * @return array An array where element 0 is the rebuilt statement and
     * element 1 is the rebuilt array of values.
     *
     */
    public function rebuildStatement($statement, $values = array(), $charset = 'UTF-8')
    {
        return $this->base_rebuilder->rebuildStatement($statement, $values, $charset);
    }

    /**
     *
     * If a '$' is found, check if it is a string inside two '$identifier$' with identifier empty or not.
     *
     * @param string $statement
     * @param array $values
     * @param string $final_statement
     * @param array $final_values
     * @param int $current_index
     * @param string $current_character
     * @param int $last_index
     * @param string $charset
     * @param int $num
     * @param int $count
     *
     * @return array
     */
    public function handleDollarCharacter($statement, $values, $final_statement, $final_values, $current_index, $current_character, $last_index, $charset, $num, $count)
    {
        $final_statement .= $current_character;
        $current_index ++;
        return array($final_statement, $final_values, $current_index, $num, $count);
    }
}