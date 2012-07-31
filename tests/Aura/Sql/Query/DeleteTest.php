<?php
namespace Aura\Sql\Query;

class DeleteTest extends AbstractQueryTest
{
    protected $query_type = 'delete';
    
    public function test()
    {
        $this->query->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir');
                    
        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM \"t1\"
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
        ";
        
        $this->assertSameSql($expect, $actual);
    }
}
