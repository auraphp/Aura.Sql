<?php
namespace Aura\Sql\Query;

class UpdateTest extends AbstractQueryTest
{
    protected $query_type = 'update';
    
    public function test()
    {
        $this->query->table('t1')
                    ->cols(['c1', 'c2', 'c3'])
                    ->set('c4', null)
                    ->set('c5', 'NOW()')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir');
                    
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
        ";
        
        $this->assertSameSql($expect, $actual);
    }
}
