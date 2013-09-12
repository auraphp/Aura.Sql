<?php
namespace Aura\Sql\Query\Pgsql;

class InsertTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Pgsql\Insert';

    public function test()
    {
        $this->query->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null)
                    ->returning(['c1', 'c2'])
                    ->returning(['c3']);

        $actual = $this->query->__toString();
        $expect = "
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
            RETURNING
                c1,
                c2,
                c3
        ";

        $this->assertSameSql($expect, $actual);
    }
}
