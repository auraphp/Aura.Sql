<?php
namespace Aura\Sql\Query\Sqlite;

class DeleteTest extends \Aura\Sql\Query\AbstractQueryTest
{
    protected $query_type = 'Sqlite\Delete';

    public function test()
    {
        $this->query->from('t1')
                    ->where('foo = ?', 'bar')
                    ->where('baz = ?', 'dib')
                    ->orWhere('zim = gir')
                    ->orderBy(['zim DESC'])
                    ->limit(5)
                    ->offset(10);
                    
        $actual = $this->query->__toString();
        $expect = "
            DELETE FROM \"t1\"
            WHERE
                foo = 'bar'
                AND baz = 'dib'
                OR zim = gir
            ORDER BY 
                zim DESC
            LIMIT 5 OFFSET 10    
        ";
        
        $this->assertSameSql($expect, $actual);
    }
}
