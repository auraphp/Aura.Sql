<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
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
     * Constructor. Instatiate a basic rebuilder and register callbacks specific to PostgreSQL syntax
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
     * @param RebuilderState $state
     *
     * @return RebuilderState
     */
    public function handleDollarCharacter($state)
    {
        $state->copyCurrentCharacter();
        $identifier = $state->capture('\\w*?\\$');
        if (!$identifier) {
            return $state;
        }

        $state->copyUntilCharacter($identifier);

        $end_tag = '$' . $identifier;

        if ($state->capture('.*?' . str_replace('$', '\\$', $end_tag))) {
            $state->copyUntilCharacter($end_tag);
        }

        return $state;
    }
}