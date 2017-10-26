<?php
namespace Aura\Sql;

use PDO;
use stdClass;

class ExtendedPdoTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtendedPdoInterface */
    protected $pdo;

    protected $data = [
        1 => 'Anna',
        2 => 'Betty',
        3 => 'Clara',
        4 => 'Donna',
        5 => 'Fiona',
        6 => 'Gertrude',
        7 => 'Hanna',
        8 => 'Ione',
        9 => 'Julia',
        10 => 'Kara',
    ];

    public function setUp()
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Need 'pdo_sqlite' to test in memory.");
        }

        $this->pdo = $this->newPdo();

        $this->createTable();
        $this->fillTable();
    }

    protected function newPdo()
    {
        return new ExtendedPdo('sqlite::memory:');
    }

    protected function createTable()
    {
        $stm = "CREATE TABLE pdotest (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(10) NOT NULL
        )";

        $this->pdo->exec($stm);
    }

    // only fills in schema 1
    protected function fillTable()
    {
        foreach ($this->data as $id => $name) {
            $this->insert(['name' => $name]);
        }
    }

    protected function insert(array $data)
    {
        $cols = array_keys($data);
        $vals = [];
        foreach ($cols as $col) {
            $vals[] = ":$col";
        }
        $cols = implode(', ', $cols);
        $vals = implode(', ', $vals);
        $stm = "INSERT INTO pdotest ({$cols}) VALUES ({$vals})";
        $this->pdo->perform($stm, $data);
    }

    public function testCall()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM blows up on this test.');
            return;
        }

        $this->pdo->sqliteCreateFunction('foo', function () {});
        $this->setExpectedException('BadMethodCallException');
        $this->pdo->sqliteNoSuchMethod();
    }

    public function testConnectionQueries()
    {
        // get default encoding
        $defaultEncoding = (new ExtendedPdo('sqlite::memory:'))->fetchValue(
            'PRAGMA encoding'
        );

        // determine a different encoding
        $newEncoding = ($defaultEncoding == 'UTF-8')
            ? 'UTF-16'
            : 'UTF-8';

        // connect with new encoding
        $pdo = new ExtendedPdo(
            'sqlite::memory:',
            null,
            null,
            [],
            ["PRAGMA encoding = '{$newEncoding}'"]
        );

        // make sure new encoding was honored
        $actual = $pdo->fetchValue('PRAGMA encoding');
    }

    public function testErrorCodeAndInfo()
    {
        $actual = $this->pdo->errorCode();
        $expect = '00000';
        $this->assertSame($expect, $actual);

        $actual = $this->pdo->errorInfo();
        $expect = ['00000', null, null];
        $this->assertSame($expect, $actual);
    }

    public function testQuery()
    {
        $stm = "SELECT * FROM pdotest";
        $sth = $this->pdo->query($stm);
        $this->assertInstanceOf('PDOStatement', $sth);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }

    public function testPerform()
    {
        $stm = "SELECT * FROM pdotest WHERE id <= :val";
        $sth = $this->pdo->perform($stm, ['val' => '5']);
        $this->assertInstanceOf('PDOStatement', $sth);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }

    public function testQueryWithArrayValues()
    {
        $stm = "SELECT * FROM pdotest WHERE id IN (:list) OR id = :id";

        $sth = $this->pdo->perform($stm, [
            'list' => [1, 2, 3, 4],
            'id' => 5
        ]);

        $this->assertInstanceOf('PDOStatement', $sth);

        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }

    public function testQueryWithFetchMode()
    {
        $stm = "SELECT id, name FROM pdotest";

        // mode and 2 args
        $sth = $this->pdo->query($stm, ExtendedPdo::FETCH_CLASS, 'stdClass', null);
        $actual = $sth->fetchAll();
        $expect = [];
        foreach ($this->data as $id => $name) {
            $expect[] = (object) [
                'id' => $id,
                'name' => $name
            ];
        }
        $this->assertEquals($expect, $actual);

        // mode and 1 arg
        $sth = $this->pdo->query($stm, ExtendedPdo::FETCH_COLUMN, 1);
        $actual = $sth->fetchAll();
        $expect = array_values($this->data);
        $this->assertSame($actual, $expect);

        // mode only
        $sth = $this->pdo->query($stm, ExtendedPdo::FETCH_ASSOC);
        $actual = $sth->fetchAll();
        $expect = [];
        foreach ($this->data as $id => $name) {
            $expect[] = [
                'id' => $id,
                'name' => $name
            ];
        }
        $this->assertEquals($expect, $actual);

    }

    public function testPrepareWithValues()
    {
        $stm = "SELECT * FROM pdotest
                 WHERE 'leave '':foo'' alone'
                 AND id IN (:list)
                 AND \"leave '':bar' alone\"";

        $sth = $this->pdo->prepareWithValues($stm, [
            'list' => ['1', '2', '3', '4', '5'],
            'foo' => 'WRONG',
            'bar' => 'WRONG',
        ]);

        $expect = str_replace(':list', ":list_0, :list_1, :list_2, :list_3, :list_4", $stm);
        $actual = $sth->queryString;
        $this->assertSame($expect, $actual);
    }

    public function testFetchAffected()
    {
        $stm = "DELETE FROM pdotest";
        $actual = $this->pdo->fetchAffected($stm);
        $expect = 10;
        $this->assertSame($expect, $actual);
    }

    public function testFetchAll()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = $this->pdo->fetchAll($stm);
        $expect = [];
        foreach ($this->data as $id => $name) {
            $expect[] = [
                'id' => $id,
                'name' => $name
            ];
        }
        $this->assertEquals($expect, $actual);
    }

    public function testYieldAll()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = [];
        foreach ($this->pdo->yieldAll($stm) as $row) {
            $actual[$row['id']] = $row['name'];
        }
        $this->assertEquals($this->data, $actual);
    }

    public function testFetchAssoc()
    {
        $stm = "SELECT * FROM pdotest ORDER BY id";
        $result = $this->pdo->fetchAssoc($stm);
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);

        // 1-based IDs, not 0-based sequential values
        $expect = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = array_keys($result);
        $this->assertEquals($expect, $actual);
    }

    public function testYieldAssoc()
    {
        $stm = "SELECT * FROM pdotest ORDER BY id";
        $actual = [];
        foreach ($this->pdo->yieldAssoc($stm) as $key => $row) {
            $this->assertEquals($key, $row['id']);
            $actual[$key] = $row['name'];
        }
        $this->assertEquals($this->data, $actual);
    }

    public function testFetchCol()
    {
        $stm = "SELECT id FROM pdotest ORDER BY id";
        $result = $this->pdo->fetchCol($stm);
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);

        // // 1-based IDs, not 0-based sequential values
        $expect = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        $this->assertEquals($expect, $result);
    }

    public function testYieldCol()
    {
        $stm = "SELECT id FROM pdotest ORDER BY id";
        $actual = [];
        foreach ($this->pdo->yieldCol($stm) as $value) {
            $actual[]= $value;
        };
        $this->assertEquals(array_keys($this->data), $actual);
    }

    public function testFetchObject()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = ?";
        $actual = $this->pdo->fetchObject($stm, [1]);
        $this->assertSame('1', $actual->id);
        $this->assertSame('Anna', $actual->name);
    }

    public function testFetchObject_withCtorArgs()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = ?";
        $actual = $this->pdo->fetchObject(
            $stm,
            [1],
            'Aura\Sql\FakeObject',
            ['bar']
        );
        $this->assertSame('1', $actual->id);
        $this->assertSame('Anna', $actual->name);
        $this->assertSame('bar', $actual->foo);
    }

    public function testFetchObjects()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = $this->pdo->fetchObjects($stm);
        $expect = [];
        foreach ($this->data as $id => $name) {
            $expect[] = (object) [
                'id' => $id,
                'name' => $name
            ];
        }
        $this->assertEquals($expect, $actual);
    }

    public function testYieldObjects()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = [];
        foreach ($this->pdo->yieldObjects($stm) as $object) {
            $actual[]= $object;
        }
        $expect = [];
        foreach ($this->data as $id => $name) {
            $expect[] = (object) [
                'id' => $id,
                'name' => $name
            ];
        }
        $this->assertEquals($expect, $actual);
    }

    public function testFetchObjects_withCtorArgs()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = $this->pdo->fetchObjects(
            $stm,
            [],
            'Aura\Sql\FakeObject',
            ['bar']
        );
        $expect = [];
        foreach ($this->data as $id => $name) {
            $object = new FakeObject('bar');
            $object->id = $id;
            $object->name = $name;
            $expect[] = $object;
        }
        $this->assertEquals($expect, $actual);
    }

    public function testYieldObjects_withCtorArgs()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = [];
        foreach ($this->pdo->yieldObjects(
            $stm,
            [],
            'Aura\Sql\FakeObject',
            ['bar']
        ) as $object)
        {
            $actual[]= $object;
        }
        $expect = [];
        foreach ($this->data as $id => $name) {
            $object = new FakeObject('bar');
            $object->id = $id;
            $object->name = $name;
            $expect[] = $object;
        }
        $this->assertEquals($expect, $actual);
    }

    public function testFetchOne()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = 1";
        $actual = $this->pdo->fetchOne($stm);
        $expect = [
            'id'   => '1',
            'name' => 'Anna',
        ];
        $this->assertEquals($expect, $actual);
    }

    public function testGroupSingleColumn()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = 1";
        $actual = $this->pdo->fetchGroup($stm);
        $expect = [
            '1' => [
                'Anna'
            ]
        ];
        $this->assertEquals($expect, $actual);
    }

    public function testGroup()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = 1";
        $actual = $this->pdo->fetchGroup($stm, [], PDO::FETCH_NAMED);
        $expect = [
            '1' => [
                [
                    'name' => 'Anna'
                ]
            ]
        ];
        $this->assertEquals($expect, $actual);
    }

    public function testFetchPairs()
    {
        $stm = "SELECT id, name FROM pdotest ORDER BY id";
        $actual = $this->pdo->fetchPairs($stm);
        $this->assertEquals($this->data, $actual);
    }

    public function testYieldPairs()
    {
        $stm = "SELECT id, name FROM pdotest ORDER BY id";
        $actual = [];
        foreach ($this->pdo->yieldPairs($stm) as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($this->data, $actual);
    }

    public function testFetchValue()
    {
        $stm = "SELECT id FROM pdotest WHERE id = 1";
        $actual = $this->pdo->fetchValue($stm);
        $expect = '1';
        $this->assertEquals($expect, $actual);
    }

    public function testQuote()
    {
        // quote a string
        $actual = $this->pdo->quote('"foo" bar \'baz\'');
        $this->assertEquals("'\"foo\" bar ''baz'''", $actual);

        // quote an integer
        $actual = $this->pdo->quote(123);
        $this->assertEquals("'123'", $actual);

        // quote a float
        $actual = $this->pdo->quote(123.456);
        $this->assertEquals("'123.456'", $actual);

        // quote an array
        $actual = $this->pdo->quote(['"foo"', 'bar', "'baz'"]);
        $this->assertEquals( "'\"foo\"', 'bar', '''baz'''", $actual);

        // quote a null
        $actual = $this->pdo->quote(null);
        $this->assertSame("''", $actual);
    }

    public function testLastInsertId()
    {
        $cols = ['name' => 'Laura'];
        $this->insert($cols);
        $expect = 11;
        $actual = $this->pdo->lastInsertId();
        $this->assertEquals($expect, $actual);
    }

    public function testTransactions()
    {
        // data
        $cols = ['name' => 'Laura'];

        // begin and rollback
        $this->assertFalse($this->pdo->inTransaction());
        $this->pdo->beginTransaction();
        $this->assertTrue($this->pdo->inTransaction());
        $this->insert($cols);
        $actual = $this->pdo->fetchAll("SELECT * FROM pdotest");
        $this->assertSame(11, count($actual));
        $rollBackResult = $this->pdo->rollback();
        $this->assertFalse($this->pdo->inTransaction());

        $actual = $this->pdo->fetchAll("SELECT * FROM pdotest");
        $this->assertSame(10, count($actual));

        // begin and commit
        $this->assertFalse($this->pdo->inTransaction());
        $this->pdo->beginTransaction();
        $this->assertTrue($this->pdo->inTransaction());
        $this->insert($cols);
        $this->pdo->commit();
        $this->assertFalse($this->pdo->inTransaction());

        $actual = $this->pdo->fetchAll("SELECT * FROM pdotest");
        $this->assertSame(11, count($actual));

        return $rollBackResult;
    }

    /**
     * @depends testTransactions
     */
    public function testRollBack($rollBackResult)
    {
        $this->assertTrue($rollBackResult);
    }

    public function testPlaceholdersInPdo()
    {
        $stm = 'SELECT * FROM pdotest WHERE id > ? AND id < :max';
        $sth = $this->pdo->prepare($stm);
        $sth->bindValue(1, '-1');
        $sth->bindValue(2, '-1'); // binding to nonexistent qmark should not cause errors
        $sth->bindValue('max', '99');
        $sth->execute();
        $res = $sth->fetchAll();
        $this->assertSame(10, count($res));
    }

    public function testPlaceholders()
    {
        $stm = 'SELECT * FROM pdotest WHERE id > ? AND id < :max';
        $val = [
            1 => '-1',
            'max' => '99',
        ];
        $res = $this->pdo->fetchAll($stm, $val);
        $this->assertSame(10, count($res));
    }

    public function testPlaceholderMissing()
    {
        $this->expectException('Aura\Sql\Exception\MissingParameter');
        $stm = "SELECT id, name FROM pdotest WHERE id = :id";
        $this->pdo->fetchOne($stm, ['foo' => 'bar']);
    }

    public function testNumberedPlaceholder()
    {
        $stm = 'SELECT * FROM pdotest WHERE id IN (?)';
        $val = [
            1 => ['1', '2', '3'],
        ];
        $res = $this->pdo->fetchAll($stm, $val);
        $this->assertSame(3, count($res));
    }

    public function testNumberedPlaceholderMissing()
    {
        $this->expectException('Aura\Sql\Exception\MissingParameter');
        $stm = "SELECT id, name FROM pdotest WHERE id = ? OR id = ?";
        $this->pdo->fetchOne($stm, [1]);
    }

    public function testZeroIndexedPlaceholders()
    {
        $stm = 'SELECT * FROM pdotest WHERE id IN (?, ?, ?)';
        $val = [1, 2, 3];
        $res = $this->pdo->fetchAll($stm, $val);
        $this->assertSame(3, count($res));
    }

    public function testPdoDependency()
    {
        // pass in ExtendedPdo to see if it can replace PDO
        $depend = new PdoDependent($this->pdo);
        $actual = $depend->fetchAll();
        $expect = [];
        foreach ($this->data as $id => $name) {
            $expect[] = [
                'id' => $id,
                'name' => $name
            ];
        }
        $this->assertEquals($expect, $actual);
    }

    public function testBindValues()
    {
        $stm = 'SELECT * FROM pdotest WHERE id = :id';

        // PDO::PARAM_INT
        $sth = $this->pdo->prepareWithValues($stm, ['id' => 1]);
        $this->assertInstanceOf('PDOStatement', $sth);

        // PDO::PARAM_BOOL
        $sth = $this->pdo->prepareWithValues($stm, ['id' => true]);
        $this->assertInstanceOf('PDOStatement', $sth);

        // PDO::PARAM_NULL
        $sth = $this->pdo->prepareWithValues($stm, ['id' => null]);
        $this->assertInstanceOf('PDOStatement', $sth);

        // string (not a special type)
        $sth = $this->pdo->prepareWithValues($stm, ['id' => 'xyz']);
        $this->assertInstanceOf('PDOStatement', $sth);

        // float (also not a special type)
        $sth = $this->pdo->prepareWithValues($stm, ['id' => 1.23]);
        $this->assertInstanceOf('PDOStatement', $sth);

        // non-bindable
        $this->expectException(
            'Aura\Sql\Exception\CannotBindValue',
            "Cannot bind value of type 'object' to placeholder 'id'"
        );
        $sth = $this->pdo->prepareWithValues($stm, ['id' => new stdClass]);
    }

    public function testWithProfileLogging()
    {
        $logger = $this->pdo->getProfiler()->getLogger();

        // leave inactive
        $this->pdo->query("SELECT 1 FROM pdotest");
        $this->assertEquals(
            0,
            count($logger->getMessages()),
            'should log nothing: not enabled yet'
        );

        // activate
        $this->pdo->getProfiler()->setActive(true);
        $this->pdo->getProfiler()->setLogFormat("{function}: {statement} {values}");

        $this->pdo->query("SELECT 1 FROM pdotest");
        $this->pdo->exec("SELECT 2 FROM pdotest");
        $this->pdo->fetchAll("SELECT 3 FROM pdotest", ['zim' => 'gir']);
        $expect = [
            'query: SELECT 1 FROM pdotest ',
            'exec: SELECT 2 FROM pdotest ',
            "perform: SELECT 3 FROM pdotest Array\n(\n    [zim] => gir\n)\n",
        ];
        $this->assertSame($logger->getMessages(), $expect);

        // de-activate
        $this->pdo->getProfiler()->setActive(false);
        $this->assertEquals(3, count($logger->getMessages()));
        $this->pdo->query("SELECT 5 FROM pdotest");
        $this->assertEquals(3, count($logger->getMessages()));
    }

    public function testNoExportOfLoginCredentials()
    {
        $pdo = new ExtendedPdo('sqlite::memory:', 'username', 'password');
        $data = $this->dump($pdo);

        // DSN
        $this->assertContains('[0]=>string(15) "sqlite::memory:"', $data);
        // username
        $this->assertContains('[1]=>string(4) "****"', $data);
        // password
        $this->assertContains('[2]=>string(4) "****"', $data);
        // options
        $this->assertContains('[3]=>array(1) {[3]=>int(2)}', $data);
        // queries
        $this->assertContains('[4]=>array(0) {}', $data);
    }

    protected function dump($pdo)
    {
        ob_start();
        var_dump($pdo);
        $data = ob_get_clean();

        // remove spaces for easier output testing
        $data = preg_replace('/\s\s+/', '', $data);

        // support hhvm tests, it leaves a space out in the var_dump output compared to php
        $data = str_replace('] ', ']', $data);

        // done
        return $data;
    }

    public function testDefaultParserIsSqlite()
    {
        $pdo = new ExtendedPdo('foobar', 'username', 'password');
        $this->assertInstanceOf('Aura\Sql\Parser\SqliteParser', $pdo->getParser());
    }

    public function testDisconnect()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->pdo->disconnect();
        $this->assertFalse($this->pdo->isConnected());
    }

    public function testGetPdo()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->assertNotEmpty($this->pdo->getPdo());
        $this->pdo->disconnect();
        $this->assertNotEmpty($this->pdo->getPdo(), 'getPdo() will re-connect if disconnected');
        $this->assertTrue($this->pdo->isConnected());
    }

    public function testQuoteName()
    {
        $pdo = new ExtendedPdo('mysql:bogus');
        $this->assertSame('`fo``o`', $pdo->quoteName('fo`o'));
        $this->assertSame('`foo`.```bar```', $pdo->quoteName('foo.`bar`'));

        $pdo = new ExtendedPdo('pgsql:bogus');
        $this->assertSame('"fo""o"', $pdo->quoteName('fo"o'));
        $this->assertSame('"foo"."""bar"""', $pdo->quoteName('foo."bar"'));

        $pdo = new ExtendedPdo('sqlite:bogus');
        $this->assertSame('"fo""o"', $pdo->quoteName('fo"o'));
        $this->assertSame('"foo"."""bar"""', $pdo->quoteName('foo."bar"'));

        $pdo = new ExtendedPdo('sqlsrv:bogus');
        $this->assertSame('[fo][o]', $pdo->quoteName('fo]o'));
        $this->assertSame('[fo[o]', $pdo->quoteName('fo[o'));
        $this->assertSame('[foo].[[bar][]', $pdo->quoteName('foo.[bar]'));

    }

    public function testGetAttribute()
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $this->assertEquals('sqlite', $driver);
    }

    public function testSetAttribute()
    {
        $result = $this->pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        $this->assertTrue($result);
    }
}
