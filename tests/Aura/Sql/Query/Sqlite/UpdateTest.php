<?php
namespace Aura\Sql\Query\Sqlite;

class UpdateTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Sqlite\Update';

    protected $expected_sql_with_flag = "
        UPDATE %s \"t1\"
            SET
                \"c1\" = :c1,
                \"c2\" = :c2,
                \"c3\" = :c3,
                \"c4\" = NULL,
                \"c5\" = NOW()
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
            LIMIT 5
    ";

    public function test()
    {
        $this->query->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->orderBy(['zim DESC', 'baz ASC'])
                    ->limit(5)
                    ->offset(10);

        $actual = $this->query->__toString();
        $expect = "
            UPDATE \"t1\"
            SET
                \"c1\" = :c1,
                \"c2\" = :c2,
                \"c3\" = :c3,
                \"c4\" = NULL,
                \"c5\" = NOW()
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
            ORDER BY 
                zim DESC,
                baz ASC
            LIMIT 5 OFFSET 10
        ";

        $this->assertSameSql($expect, $actual);
    }

    public function testOrAbort()
    {
        $this->query->orAbort()
                    ->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->limit(5);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR ABORT');

        $this->assertSameSql($expect, $actual);
    }

    public function testOrFail()
    {
        $this->query->orFail()
                    ->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->limit(5);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR FAIL');

        $this->assertSameSql($expect, $actual);
    }

    public function testOrIgnore()
    {
        $this->query->orIgnore()
                    ->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->limit(5);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR IGNORE');

        $this->assertSameSql($expect, $actual);
    }

    public function testOrReplace()
    {
        $this->query->orReplace()
                    ->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->limit(5);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR REPLACE');

        $this->assertSameSql($expect, $actual);
    }
    
    public function testOrRollback()
    {
        $this->query->orRollback()
                    ->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->limit(5);

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'OR ROLLBACK');

        $this->assertSameSql($expect, $actual);
    }
}
