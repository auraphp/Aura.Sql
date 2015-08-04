<?php
namespace Aura\Sql;


class RebuilderTest extends \PHPUnit_Framework_TestCase
{
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

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testSingleLineCommentAsLastLine()
    {
        $stm = "SELECT *
                FROM pdotest
                -- Don't bind :foo";
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
        );

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testUnclosedMultiLineComment()
    {
        $stm = "SELECT *
                FROM pdotest
                /* Don't bind :foo";
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
        );

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = $stm;
        $this->assertSame($expect, $actual);
    }

    public function testMinusCharacter()
    {
        $stm = "SELECT -:foo
                FROM pdotest";
        $values =  array(
            'foo' => array(2),
        );

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = str_replace(':foo', ':foo_0', $stm);
        $this->assertSame($expect, $actual);
    }

    public function testSlashCharacter()
    {
        $stm = "SELECT 2 / :foo
                FROM pdotest";
        $values =  array(
            'foo' => array(4),
        );

        $rebuilder = new Rebuilder();

        list($actual, $values) = $rebuilder->rebuildStatement($stm, $values);

        $expect = str_replace(':foo', ':foo_0', $stm);
        $this->assertSame($expect, $actual);
    }

    public function testBindNullFirstParameter ()
    {
        $rebuilder = new Rebuilder();
        $result = $rebuilder->rebuildStatement('SELECT * FROM test WHERE column_one = ?', array(null));
        $this->assertTrue(array_key_exists(1, $result[1]));
    }

    public function testDoubleSingleQuoteInCharacterString ()
    {
        $stm = "SELECT 'this :foo shouldn''t be modified :bar' FROM pdotest";
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
            'bar' => array('should', 'be', 'too')
        );
        $rebuilder = new Rebuilder();

        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);

        $this->assertSame($stm, $result);
    }

    public function testEscapedSingleQuoteInCharacterString ()
    {
        $rebuilder = new Rebuilder();
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
            'bar' => array('should', 'be', 'too')
        );

        $stm = "SELECT 'this :bar shouldn\\'t be :foo modified' FROM pdotest";
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);

        $stm = "SELECT 'this :bar shouldn\\\\\\'t be :foo modified' FROM pdotest";
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);
    }

    public function testDoubleDoubleQuoteInCharacterString ()
    {
        $rebuilder = new Rebuilder();

        $stm = 'SELECT "this ""should not"" be modified" FROM pdotest';

        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
            'bar' => array('should', 'be', 'too')
        );

        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);

        $this->assertSame($stm, $result);
    }

    public function testEscapedDoubleQuoteInCharacterString ()
    {
        $rebuilder = new Rebuilder();
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
            'bar' => array('should', 'be', 'too')
        );

        $stm = 'SELECT "this :bar \\"should not\\" be :foo modified" FROM pdotest';
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);

        $stm = 'SELECT "this :bar \\\\\\"should not\\\\\\" be :foo modified" FROM pdotest';
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);
    }

    public function testMultiColon()
    {
        $stm = "SELECT ::foo  FROM pdotest";
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
        );
        $rebuilder = new Rebuilder();

        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);

        $this->assertSame($stm, $result);

        $stm = "SELECT :::foo  FROM pdotest";
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);
    }

    public function testUnfinishedCharacterString ()
    {
        $values =  array(
            'foo' => array('should', 'be', 'left', 'alone'),
        );
        $rebuilder = new Rebuilder();

        $stm = "SELECT 'this :foo shouldn''t be modified";
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);

        $stm = 'SELECT "this :foo should not be modified';
        list($result, $returnedValues) = $rebuilder->rebuildStatement($stm, $values);
        $this->assertSame($stm, $result);
    }
}