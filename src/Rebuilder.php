<?php
namespace Aura\Sql;

class Rebuilder
{
	public function __construct(ExtendedPdo $pdo)
	{
		$this->pdo = $pdo;
	}

    /**
     *
     * Rebuilds a statement with array values replaced into placeholders.
     *
     * @param string $statement The statement to rebuild.
     *
     * @param array $values The values to bind and/or replace into a statement.
     *
     * @return array An array where element 0 is the rebuilt statement and
     * element 1 is the rebuilt array of values.
     *
     */
    public function __invoke($statement, $values)
    {
        $bind = $this->newBindTracker($values);
        $statement = $this->rebuildStatement($statement, $bind);
        return array($statement, $bind->final_values);
    }

    /**
     *
     * Returns a new anonymous object to track bind values.
     *
     * @param array $values The values to bind and/or replace into a statement.
     *
     * @return object
     *
     */
    protected function newBindTracker($values)
    {
        // anonymous object to track preparation info
        return (object) array(
            // how many numbered placeholders in the original statement
            'num' => 0,
            // how many numbered placeholders to actually be bound; this may
            // differ from 'num' in that some numbered placeholders may get
            // replaced with quoted CSV strings
            'count' => 0,
            // initial values to be bound
            'values' => $values,
            // named and numbered placeholders to bind at the end
            'final_values' => array(),
        );
    }

    /**
     *
     * Given a statement, rebuilds it with array values embedded.
     *
     * @param string $statement The SQL statement.
     *
     * @param object $bind The bind-values tracker.
     *
     * @return string The rebuilt statement.
     *
     */
    protected function rebuildStatement($statement, $bind)
    {
        // find all parts not inside quotes or backslashed-quotes
        $apos = "'";
        $quot = '"';
        $parts = preg_split(
            "/(($apos+|$quot+|\\$apos+|\\$quot+).*?)\\2/m",
            $statement,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        return $this->rebuildParts($parts, $bind);
    }

    /**
     *
     * Given an array of statement parts, rebuilds each part.
     *
     * @param array $parts The statement parts.
     *
     * @param object $bind The bind-values tracker.
     *
     * @return string The rebuilt statement.
     *
     */
    protected function rebuildParts($parts, $bind)
    {
        // loop through the non-quoted parts (0, 3, 6, 9, etc.)
        $k = count($parts);
        for ($i = 0; $i <= $k; $i += 3) {
            $parts[$i] = $this->rebuildPart($parts[$i], $bind);
        }
        return implode('', $parts);
    }

    /**
     *
     * Rebuilds a single statement part.
     *
     * @param string $part The statement part.
     *
     * @param object $bind The bind-values tracker.
     *
     * @return string The rebuilt statement.
     *
     */
    protected function rebuildPart($part, $bind)
    {
        // split into subparts by ":name" and "?"
        $subs = preg_split(
            "/(:[a-zA-Z_][a-zA-Z0-9_]*)|(\?)/m",
            $part,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // check subparts to convert bound arrays to quoted CSV strings
        $subs = $this->prepareValuePlaceholders($subs, $bind);

        // reassemble
        return implode('', $subs);
    }

    /**
     *
     * Prepares the sub-parts of a query with placeholders.
     *
     * @param array $subs The query subparts.
     *
     * @param object $bind The preparation info object.
     *
     * @return array The prepared subparts.
     *
     */
    protected function prepareValuePlaceholders(array $subs, $bind)
    {
        foreach ($subs as $i => $sub) {
            $char = substr($sub, 0, 1);
            if ($char == '?') {
                $subs[$i] = $this->prepareNumberedPlaceholder($sub, $bind);
            }

            if ($char == ':') {
                $subs[$i] = $this->prepareNamedPlaceholder($sub, $bind);
            }
        }

        return $subs;
    }

    /**
     *
     * Bind or quote a numbered placeholder in a query subpart.
     *
     * @param string $sub The query subpart.
     *
     * @param object $bind The preparation info object.
     *
     * @return string The prepared query subpart.
     *
     */
    protected function prepareNumberedPlaceholder($sub, $bind)
    {
        // what numbered placeholder is this in the original statement?
        $bind->num ++;

        // is the corresponding data element an array?
        $bind_array = isset($bind->values[$bind->num])
                   && is_array($bind->values[$bind->num]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = $this->pdo->quote($bind->values[$bind->num]);
        } else {
            // increase the count of numbered placeholders to be bound
            $bind->count ++;
            $bind->final_values[$bind->count] = $bind->values[$bind->num];
        }

        return $sub;
    }

    /**
     *
     * Bind or quote a named placeholder in a query subpart.
     *
     * @param string $sub The query subpart.
     *
     * @param object $bind The preparation info object.
     *
     * @return string The prepared query subpart.
     *
     */
    protected function prepareNamedPlaceholder($sub, $bind)
    {
        $name = substr($sub, 1);

        // is the corresponding data element an array?
        $bind_array = isset($bind->values[$name])
                   && is_array($bind->values[$name]);
        if ($bind_array) {
            // PDO won't bind an array; quote and replace directly
            $sub = $this->pdo->quote($bind->values[$name]);
        } else {
            // not an array, retain the placeholder for later
            $bind->final_values[$name] = $bind->values[$name];
        }

        return $sub;
    }
}