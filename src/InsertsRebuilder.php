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
 * This support class for ExtendedPdo rebuilds an SQL insert|replace statement with multiline pattern like:
 *
 * $stm = "INSERT INTO test_table(id, name, date, comment) VALUES (?, ?, DATE(NOW()), 'test string'), ... ON DUPLICATE KEY UPDATE name=VALUES(name), date=VALUES(date)";
 * $bind_values = [[[1, 'name1'], [2, 'name2']]];
 * $pdo->fetchAffected($stm, $bind_values);
 *
 * Named or numbered placeholders allowed
 * 
 * @package Aura.Sql
 *
 */
class InsertsRebuilder
{
    /**
     *
     * The calling ExtendedPdo object.
     *
     * @var ExtendedPdo
     *
     */
    protected $xpdo;

    /**
     *
     * Constructor.
     *
     * @param ExtendedPdo $xpdo The calling ExtendedPdo object.
     *
     */
    public function __construct(ExtendedPdo $xpdo)
    {
        $this->xpdo = $xpdo;
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
        list($statement, $values) = $this->rebuildStatement($statement, $values);
        return array($statement, $values);
    }
    
    protected function rebuildStatement($statement, $values){
        $subs = preg_split(
            "/(VALUES|VALUE)[\t\s\n\r]*(\(.+\))[\s]*,[\s]*\.\.\./im",
            $statement,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        if(sizeof($subs) > 1 && is_array(current($values[0]))){        //do it!
            $subs = array_map(function ($v){ return trim($v);}, $subs);
            $vals_pattern = $subs[2];
            $sql_parts = array();
            $array = $values[0];        //two-dimensional array
            foreach($array as $array_line){
                $sql_part = $this->placeValues($vals_pattern, $array_line);
                $sql_parts[] = $sql_part;
            }
            $subs[2] = implode(', ', $sql_parts);
            unset($values[0]);
        }
        
        ksort($subs);
        $statement = implode(' ', $subs);
        
        return array($statement, $values);
    }
    
    protected function placeValues($statement, $values)
    {
        $rebuilder = new Rebuilder($this->xpdo);
        $rebuilder->rebuildMode(1);
        list($statement, $values) = $rebuilder->__invoke($statement, $values);
        return $statement;
    }

}