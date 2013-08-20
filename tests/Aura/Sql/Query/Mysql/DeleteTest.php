<?php
namespace Aura\Sql\Query\Mysql;

class DeleteTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Mysql\Delete';

    protected $expected_sql_with_flag = "
        DELETE %s FROM \"t1\"
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
    ";

    public function test()
    {
        $this->query->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->limit(5);

        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM \"t1\"
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
            LIMIT 5
        ";

        $this->assertSameSql($expect, $actual);
    }

    public function testLowPriority()
    {
        $this->query->lowPriority()
                    ->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir');

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'LOW_PRIORITY');

        $this->assertSameSql($expect, $actual);
    }

    public function testQuick()
    {
        $this->query->quick()
                    ->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir');

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'QUICK');

        $this->assertSameSql($expect, $actual);
    }

    public function testIgnore()
    {
        $this->query->ignore()
                    ->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir');

        $actual = $this->query->__toString();
        $expect = sprintf($this->expected_sql_with_flag, 'IGNORE');

        $this->assertSameSql($expect, $actual);
    }
}
