<?php
namespace Aura\Sql;

class ColumnTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $info = [
            'name' => 'cost',
            'type' => 'numeric',
            'size' => 10,
            'scale' => 2,
            'notnull' => true,
            'default' => null,
            'autoinc' => false,
            'primary' => false,
        ];
        
        $col = new Column(
            $info['name'],
            $info['type'],
            $info['size'],
            $info['scale'],
            $info['notnull'],
            $info['default'],
            $info['autoinc'],
            $info['primary']
        );
        
        foreach ($info as $key => $expect) {
            $this->assertSame($expect, $col->$key);
        }
    }
    
    public function test__set_state()
    {
        $info = [
            'name' => 'cost',
            'type' => 'numeric',
            'size' => 10,
            'scale' => 2,
            'notnull' => true,
            'default' => null,
            'autoinc' => false,
            'primary' => false,
        ];
        
        $col = new Column(
            $info['name'],
            $info['type'],
            $info['size'],
            $info['scale'],
            $info['notnull'],
            $info['default'],
            $info['autoinc'],
            $info['primary']
        );
        
        eval('$actual = ' . var_export($col, true) . ';');
        
        foreach ($info as $key => $expect) {
            $this->assertSame($expect, $actual->$key);
        }
    }
}
