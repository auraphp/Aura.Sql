<?php
namespace Aura\Sql;

class RebuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testIssue111()
    {
        $xpdo = new ExtendedPdo('sqlite::memory:');
        $rebuilder = new Rebuilder($xpdo);
        $expect = "
            SELECT id
            FROM users
            WHERE created_at >= ':createdDate 00:00:00'::TIMESTAMP
            AND Foo::bar = 'something:else'
        ";
        list ($actual, $values) = $rebuilder($expect, array());
        $this->assertSame($expect, $actual);
    }
}
