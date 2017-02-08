<?php
namespace Aura\Sql\Parser;

class StateTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyStatement()
    {
        $state = new State('');;
        $this->assertTrue($state->done());
    }

    public function testNoAction()
    {
        $stmt = 'SELECT * FROM test';
        $values = array(
            "foo" => "bar",
        );

        $state = new State($stmt, $values);
        $this->assertSame($stmt, $state->getStatement());
        $this->assertSame('', $state->getFinalStatement());
        $this->assertSame(array(), $state->getValuesToBind());
    }

    public function testNextCharacters()
    {
        $stmt = 'SELECT * FROM test';
        $state = new State($stmt);
        $this->assertSame('S', $state->getCurrentCharacter());
        $this->assertTrue($state->nextCharactersAre('ELECT *'));
    }

    public function testCurrentCharacter()
    {
        $stmt = 'SELECT * FROM test';
        $state = new State($stmt);
        $this->assertSame('S', $state->getCurrentCharacter());

        $stmt = 'ฒ';
        $state = new State($stmt);
        $this->assertSame('ฒ', $state->getCurrentCharacter());
    }

    public function getNamedParameterValue()
    {
        $stmt = '';
        $values = array(
            "foo" => "bar",
        );

        $state = new State($stmt, $values);
        $this->assertSame("bar", $state->getNamedParameterValue("foo"));
    }

    public function testCopyCharacter()
    {
        $stmt = "SELECT '฿๑๑๗' FROM test";
        $state = new State($stmt);
        $this->assertSame('', $state->getFinalStatement());
        $state->copyCurrentCharacter();
        $this->assertSame("S", $state->getFinalStatement());
        $this->assertSame("E", $state->getCurrentCharacter());
        for ($i = 0; $i < 8; $i++) {
            $state->copyCurrentCharacter();
        }
        $this->assertSame("SELECT '฿", $state->getFinalStatement());
        $this->assertSame("๑", $state->getCurrentCharacter());
    }

    public function testCopyCharacters()
    {
        $stmt = "SELECT '฿๑๑๗' FROM test";
        $state = new State($stmt);
        $this->assertSame('', $state->getFinalStatement());
        $state->copyCharacters(9);
        $this->assertSame("SELECT '฿", $state->getFinalStatement());
        $this->assertSame("๑", $state->getCurrentCharacter());
        $state->copyCharacters(0);
        $this->assertSame("SELECT '฿", $state->getFinalStatement());
    }

    public function testCopyIdentifier()
    {
        $stmt = "SELECT '฿๑๑๗' FROM test";
        $state = new State($stmt);
        $state->copyIdentifier();
        $this->assertSame("SELECT", $state->getFinalStatement());

        $state->copyIdentifier();
        $this->assertSame("SELECT", $state->getFinalStatement());
    }

    public function testPassString()
    {
        $stmt = "SELECT '฿๑๑๗' FROM test";
        $state = new State($stmt);
        $state->passString('SELECT');
        $this->assertSame(6, $state->getCurrentIndex());
        $this->assertSame('', $state->getFinalStatement());
    }

    public function testAddString()
    {
        $stmt = "SELECT '฿๑๑๗' FROM test";
        $state = new State($stmt);
        $state->addStringToStatement('Test');
        $this->assertSame(0, $state->getCurrentIndex());
        $this->assertSame('Test', $state->getFinalStatement());
    }

    public function testCopyUntil()
    {
        $stmt = "SELECT '฿๑๑๗' FROM test";
        $state = new State($stmt);
        $state->copyUntilCharacter('๑');
        $this->assertSame(10, $state->getCurrentIndex());
        $this->assertSame("SELECT '฿๑", $state->getFinalStatement());
    }

    public function testGetIdentifier()
    {
        $stmt = "test";
        $state = new State($stmt);
        $identifier = $state->getIdentifier();
        $this->assertSame('test', $identifier);

        $stmt = "";
        $state = new State($stmt);
        $identifier = $state->getIdentifier();
        $this->assertSame('', $identifier);
    }

    public function testStoreSimpleValue()
    {
        $stmt = '';
        $state = new State($stmt);
        $name = $state->storeValueToBind('foo', 32);
        $this->assertSame('foo', $name);
        $name = $state->storeValueToBind('foo', 10);
        $this->assertSame('foo_0', $name);
        $name = $state->storeValueToBind('foo', 'a');
        $this->assertSame('foo_1', $name);
        $expected = array(
            'foo' => 32,
            'foo_0' => 10,
            'foo_1' => 'a',
        );
        $this->assertSame($expected, $state->getValuesToBind());
    }

    public function testGetNamedValue()
    {
        $stmt = '';
        $values = array(
            'foo' => array('bar', 'baz'),
        );
        $state = new State($stmt, $values);
        $expected = $values['foo'];
        $this->assertSame($expected, $state->getNamedParameterValue('foo'));
        $this->assertNull($state->getNamedParameterValue('bar'));
    }

    /**
     * @expectedException Aura\Sql\Exception\MissingParameter
     */
    public function testGetNumberedValue()
    {
        $stmt = '';
        $values = array("foo", "bar", "baz");
        $state = new State($stmt, $values);
        $this->assertSame("foo", $state->getFirstUnusedNumberedValue());
        $this->assertSame("bar", $state->getFirstUnusedNumberedValue());
        $this->assertSame("baz", $state->getFirstUnusedNumberedValue());
        $state->getFirstUnusedNumberedValue();

        $values = array("foo" => 1);
        $state = new State($stmt, $values);
        $state->getFirstUnusedNumberedValue();

        $values = array("fizz" => 1, "buzz");
        $state = new State($stmt, $values);
        $this->assertSame("buzz", $state->getFirstUnusedNumberedValue());
        $state->getFirstUnusedNumberedValue();
    }

    public function testStoreNumberedValue()
    {
        $stmt = '';
        $state = new State($stmt);
        $state->storeNumberedValueToBind("foo");
        $state->storeNumberedValueToBind("bar");
        $state->storeNumberedValueToBind("baz");
        $expected = array(
            1 => "foo",
            2 => "bar",
            3 => "baz",
        );
        $this->assertSame($expected, $state->getValuesToBind());
    }

    public function testGetCharset()
    {
        $state = new State('', array(), 'US-ASCII');
        $this->assertSame('US-ASCII', $state->getCharset());
    }
}
