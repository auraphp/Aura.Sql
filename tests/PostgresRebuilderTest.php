<?php
namespace Aura\Sql;


class PostgresRebuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testDollarStringWithEmptyIdentifier()
    {
        $stm = 'SELECT $$ This should not be bound :foo $$ FROM pdotest';
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
        );

        $rebuilder = new PostgresRebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);

        $stm = 'SELECT $$$ This should not be bound :foo $$ FROM pdotest';
        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testDollarStringWithNonEmptyIdentifier()
    {
        $stm = 'SELECT $a$ This should not be bound :foo $a$ FROM pdotest';
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
        );

        $rebuilder = new PostgresRebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testDollarStringWithMismatchedIdentifier()
    {
        $stm = 'SELECT $a$ :list $b$ FROM pdotest';
        $values =  array(
            'list' => array(1, 2, 3),
        );

        $rebuilder = new PostgresRebuilder();

        list($actual, $actual_values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = str_replace(':list', ':list_0, :list_1, :list_2', $stm);
        $expected_values = array(
            'list_0' => 1,
            'list_1' => 2,
            'list_2' => 3,
        );
        $this->assertSame($expect, $actual);
        $this->assertEquals($expected_values, $actual_values);
    }

    public function testDollarButNotStringIdentifier()
    {
        $stm = 'SELECT $a $ :list $a$ FROM pdotest';
        $values =  array(
            'list' => array(1, 2, 3),
        );

        $rebuilder = new PostgresRebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = str_replace(':list', ':list_0, :list_1, :list_2', $stm);
        $expected_values = array(
            'list_0' => 1,
            'list_1' => 2,
            'list_2' => 3,
        );
        $this->assertSame($expect, $actual);
        $this->assertEquals($expected_values, $values);
    }

}