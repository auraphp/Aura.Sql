<?php
/**
 * This file is part of Aura for PHP.
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace Aura\Sql\Rebuilder;

/**
 * Replace array values
 * @package aura/sql
 */
class ArrayParameterRebuilder implements RebuilderInterface
{
    /**
     * @param \Aura\Sql\Rebuilder\Query $query
     * @return \Aura\Sql\Rebuilder\Query the rebuilt query
     */
    public function rebuild(Query $query)
    {
        $result = clone($query);
        foreach ($query->getValues() as $name => $value) {
            if (is_array($value)) {
                $result = $this->replaceArrayWithValues($result, $name, $value);
            }
        }
        return $result;
    }

    /**
     * @param \Aura\Sql\Rebuilder\Query $query
     * @param \string                   $name
     * @param array                     $values
     * @return \Aura\Sql\Rebuilder\Query
     */
    private function replaceArrayWithValues(Query $query, $name, array $values)
    {
        $count      = 0;
        $new_names  = [];
        $all_values = $query->getValues();
        foreach ($values as $value) {
            $new_name              = $name . '_' . ($count++);
            $all_values[$new_name] = $value;
            $new_names[]           = ':' . $new_name;
        }
        unset($all_values[$name]);
        $sql = str_replace('(:' . $name . ')', '(' . join(',', $new_names) . ')', $query->getStatement());
        return new Query($sql, $all_values);
    }
}
