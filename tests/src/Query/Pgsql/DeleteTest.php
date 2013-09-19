<?php
namespace Aura\Sql\Query\Pgsql;

class DeleteTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Pgsql\Delete';

    public function test()
    {
        $this->query->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->returning(['foo', 'baz', 'zim']);

        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM \"t1\"
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
            RETURNING
                foo,
                baz,
                zim
        ";

        $this->assertSameSql($expect, $actual);
    }
}
