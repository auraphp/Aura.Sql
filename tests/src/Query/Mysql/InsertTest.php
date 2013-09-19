<?php
namespace Aura\Sql\Query\Mysql;

class InsertTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Mysql\Insert';

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

    public function testHighPriority()
    {
        $this->query->highPriority()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'HIGH_PRIORITY');

        $this->assertSameSql($expect, $actual);
    }

    public function testLowPriority()
    {
        $this->query->lowPriority()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'LOW_PRIORITY');

        $this->assertSameSql($expect, $actual);
    }

    public function testDelayed()
    {
        $this->query->delayed()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'DELAYED');

        $this->assertSameSql($expect, $actual);
    }

    public function testIgnore()
    {
        $this->query->ignore()
                    ->into('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', 'NOW()')
                    ->set('c5', null);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'IGNORE');

        $this->assertSameSql($expect, $actual);
    }
}
