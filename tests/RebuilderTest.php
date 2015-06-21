<?php
namespace Aura\Sql;


class RebuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testPostgresCastOperator()
    {
        $stm = "SELECT name::TEXT FROM pdotest WHERE id = :id";
        $values = array(
            'TEXT' => array('this', 'should', 'be', 'left', 'alone'),
            'id' => 5
        );

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->__invoke($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testPostgresJsonOperators()
    {
        $stm = 'SELECT CAST(\'{"a": 1, "b": 2, "c": 3}\' AS JSONB) ? \'b\'';
        $values = array(array('this', 'should', 'be', 'left', 'alone'));

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->__invoke($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testCommentedSQL()
    {
        $stm = "SELECT * -- Don't bind :foo
                FROM pdotest
                /*
                Don't bind :bar
                 */";
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
            'bar' => array('should', 'be', 'too')
        );

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->__invoke($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testBindNullFirstParameter ()
    {
        $rebuilder = new Rebuilder();
        $result = $rebuilder('SELECT * FROM test WHERE column_one = ?', array(null));
        $this->assertTrue(array_key_exists(1, $result[1]));
    }
}