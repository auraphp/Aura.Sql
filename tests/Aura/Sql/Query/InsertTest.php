<?php
namespace Aura\Sql\Query;

class InsertTest extends AbstractQueryTest
{
    protected $query_type = 'insert';
    
    public function test()
    {
        $this->query->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);
        
        $actual = $this->query->__toString();
        $expect = '
            INSERT INTO \"t1\" (
                \"c1\",
                \"c2\",
                \"c3\",
                \"c4\",
                \"c5\"
            ) VALUES (
                :c1,
                :c2,
                :c3,
                NOW(),
                NULL
            )
        ';
    }
}
