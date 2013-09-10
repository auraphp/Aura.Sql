<?php
namespace Aura\Sql\Query\Sqlite;

class InsertTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Sqlite\Insert';

    protected $expected_sql_with_flag = "
        INSERT %s INTO \"t1\" (
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
    ";

    public function test()
    {
        $this->query->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

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
        ";

        $this->assertSameSql($expect, $actual);
    }

    public function testOrAbort()
    {
        $this->query->orAbort()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR ABORT');

        $this->assertSameSql($expect, $actual);
    }

    public function testOrFail()
    {
        $this->query->orFail()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR FAIL');

        $this->assertSameSql($expect, $actual);
    }

    public function testOrIgnore()
    {
        $this->query->orIgnore()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR IGNORE');

        $this->assertSameSql($expect, $actual);
    }

    public function testOrReplace()
    {
        $this->query->orReplace()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR REPLACE');

        $this->assertSameSql($expect, $actual);
    }
    
    public function testOrRollback()
    {
        $this->query->orRollback()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR ROLLBACK');

        $this->assertSameSql($expect, $actual);
    }
}
