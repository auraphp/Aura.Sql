<?php
namespace Aura\Sql;

use PDO;

abstract class AbstractExtendedPdoTest extends \PHPUnit_Framework_TestCase
{
    protected $pdo;
    
    protected $data = array(
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
    );
    
    public function setUp()
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Need 'pdo_sqlite' to test in memory.");
        }

        $this->pdo = $this->newExtendedPdo();

        $this->createTable();
        $this->fillTable();
    }
    
    abstract protected function newExtendedPdo();
    
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
            $this->insert(array('name' => $name));
        }
    }
    
    protected function insert(array $data)
    {
        $cols = array_keys($data);
        $vals = array();
        foreach ($cols as $col) {
            $vals[] = ":$col";
        }
        $cols = implode(', ', $cols);
        $vals = implode(', ', $vals);
        $stm = "INSERT INTO pdotest ({$cols}) VALUES ({$vals})";
        $this->pdo->perform($stm, $data);
    }
    
    abstract public function testGetPdo();
    
    public function testErrorCodeAndInfo()
    {
        $actual = $this->pdo->errorCode();
        $expect = '00000';
        $this->assertSame($expect, $actual);
        
        $actual = $this->pdo->errorInfo();
        $expect = array('00000', null, null);
        $this->assertSame($expect, $actual);
    }
    
    public function testSetAndGetAttribute()
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $this->assertFalse($pdo->isConnected());
        
        $pdo->setAttribute(Pdo::ATTR_ERRMODE, ExtendedPdo::ERRMODE_WARNING);
        $this->assertFalse($pdo->isConnected());
        
        $actual = $pdo->getAttribute(Pdo::ATTR_ERRMODE);
        $this->assertSame(Pdo::ERRMODE_WARNING, $actual);
        $this->assertTrue($pdo->isConnected());
        
        // set again now that we're connected
        $pdo->setAttribute(Pdo::ATTR_ERRMODE, ExtendedPdo::ERRMODE_EXCEPTION);
        $actual = $pdo->getAttribute(Pdo::ATTR_ERRMODE);
        $this->assertSame(Pdo::ERRMODE_EXCEPTION, $actual);
    }
    
    public function testQuery()
    {
        $stm = "SELECT * FROM pdotest";
        $sth = $this->pdo->query($stm);
        $this->assertInstanceOf('PDOStatement', $sth);
        $result = $sth->fetchAll(Pdo::FETCH_ASSOC);
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }
    
    public function testPerform()
    {
        $stm = "SELECT * FROM pdotest WHERE id <= :val";
        $sth = $this->pdo->perform($stm, array('val' => '5'));
        $this->assertInstanceOf('PDOStatement', $sth);
        $result = $sth->fetchAll(Pdo::FETCH_ASSOC);
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }
    
    public function testQueryWithArrayValues()
    {
        $stm = "SELECT * FROM pdotest WHERE id IN (:list) OR id = :id";
        
        $sth = $this->pdo->perform($stm, array(
            'list' => array(1, 2, 3, 4),
            'id' => 5
        ));

        $this->assertInstanceOf('PDOStatement', $sth);
        
        $result = $sth->fetchAll(Pdo::FETCH_ASSOC);
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }
    
    public function testQueryWithFetchMode()
    {
        $stm = "SELECT id, name FROM pdotest";
        
        // mode and 2 args
        $sth = $this->pdo->query($stm, ExtendedPdo::FETCH_CLASS, 'StdClass', array());
        $actual = $sth->fetchAll();
        $expect = array();
        foreach ($this->data as $id => $name) {
            $expect[] = (object) array(
                'id' => $id,
                'name' => $name
            );
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
        $expect = array();
        foreach ($this->data as $id => $name) {
            $expect[] = array(
                'id' => $id,
                'name' => $name
            );
        }
        $this->assertEquals($expect, $actual);
        
    }
    
    public function testPrepareWithValues()
    {
        $stm = "SELECT * FROM pdotest
                 WHERE 'leave '':foo'' alone'
                 AND id IN (:list)
                 AND \"leave '':bar' alone\"";
        
        $sth = $this->pdo->prepareWithValues($stm, array(
            'list' => array('1', '2', '3', '4', '5'),
            'foo' => 'WRONG',
            'bar' => 'WRONG',
        ));
        
        $expect = str_replace(':list', "'1', '2', '3', '4', '5'", $stm);
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
        $expect = array();
        foreach ($this->data as $id => $name) {
            $expect[] = array(
                'id' => $id,
                'name' => $name
            );
        }
        $this->assertEquals($expect, $actual);
    }
    
    public function testFetchAllWithCallback()
    {
        $stm = "SELECT * FROM pdotest ORDER BY id LIMIT 5";
        $result = $this->pdo->fetchAll($stm, array(), function ($row) {
            return array(strtolower($row['name']));
        });
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
        $expect = array(
            array('anna'),
            array('betty'),
            array('clara'),
            array('donna'),
            array('fiona'),
        );
        $this->assertEquals($expect, $result);
    }
    
    public function testFetchAssoc()
    {
        $stm = "SELECT * FROM pdotest ORDER BY id";
        $result = $this->pdo->fetchAssoc($stm);
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
        
        // 1-based IDs, not 0-based sequential values
        $expect = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $actual = array_keys($result);
        $this->assertEquals($expect, $actual);
    }
    
    public function testFetchAssocWithCallback()
    {
        $stm = "SELECT * FROM pdotest ORDER BY id LIMIT 5";
        $result = $this->pdo->fetchAssoc($stm, array(), function ($row) {
            return array(strtolower($row['name']));
        });
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
        
        // 1-based IDs, not 0-based sequential values
        $expect = array(1, 2, 3, 4, 5);
        $actual = array_keys($result);
        $this->assertEquals($expect, $actual);        
        
        $expect = array(
            array('anna'),
            array('betty'),
            array('clara'),
            array('donna'),
            array('fiona'),
        );
        $actual = array_values($result);
        $this->assertEquals($expect, $actual);
    }

    public function testFetchCol()
    {
        $stm = "SELECT id FROM pdotest ORDER BY id";
        $result = $this->pdo->fetchCol($stm);
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
        
        // // 1-based IDs, not 0-based sequential values
        $expect = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10');
        $this->assertEquals($expect, $result);
    }
    
    public function testFetchColWithCallback()
    {
        $stm = "SELECT id FROM pdotest ORDER BY id LIMIT 5";
        $result = $this->pdo->fetchCol($stm, array(), function ($val) {
            return $val * 2;
        });
        $expect = 5;
        $actual = count($result);
        $this->assertEquals($expect, $actual);

        $expect = array(2, 4, 6, 8, 10);
        $this->assertEquals($expect, $result);
    }

    public function testFetchObject()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = 1";
        $actual = $this->pdo->fetchObject($stm);
        $this->assertSame('1', $actual->id);
        $this->assertSame('Anna', $actual->name);
    }
    
    public function testFetchObjects()
    {
        $stm = "SELECT * FROM pdotest";
        $actual = $this->pdo->fetchObjects($stm);
        $expect = array();
        foreach ($this->data as $id => $name) {
            $expect[] = (object) array(
                'id' => $id,
                'name' => $name
            );
        }
        $this->assertEquals($expect, $actual);
    }
    
    public function testFetchOne()
    {
        $stm = "SELECT id, name FROM pdotest WHERE id = 1";
        $actual = $this->pdo->fetchOne($stm);
        $expect = array(
            'id'   => '1',
            'name' => 'Anna',
        );
        $this->assertEquals($expect, $actual);
    }
    
    public function testFetchPairs()
    {
        $stm = "SELECT id, name FROM pdotest ORDER BY id";
        $actual = $this->pdo->fetchPairs($stm);
        $expect = array(
          1  => 'Anna',
          2  => 'Betty',
          3  => 'Clara',
          4  => 'Donna',
          5  => 'Fiona',
          6  => 'Gertrude',
          7  => 'Hanna',
          8  => 'Ione',
          9  => 'Julia',
          10 => 'Kara',
        );
        $this->assertEquals($expect, $actual);
    }
    
    public function testFetchPairsWithCallback()
    {
        $stm = "SELECT id, name FROM pdotest ORDER BY id";
        $actual = $this->pdo->fetchPairs($stm, array(), function ($row) {
            return array((string) $row[0], strtolower($row[1]));
        });
        $expect = array(
          '1'  => 'anna',
          '2'  => 'betty',
          '3'  => 'clara',
          '4'  => 'donna',
          '5'  => 'fiona',
          '6'  => 'gertrude',
          '7'  => 'hanna',
          '8'  => 'ione',
          '9'  => 'julia',
          '10' => 'kara',
        );
        $this->assertSame($expect, $actual);
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
        $actual = $this->pdo->quote(array('"foo"', 'bar', "'baz'"));
        $this->assertEquals( "'\"foo\"', 'bar', '''baz'''", $actual);
        
        // quote a null
        $actual = $this->pdo->quote(null);
        $this->assertSame("''", $actual);
    }
    
    public function testLastInsertId()
    {
        $cols = array('name' => 'Laura');
        $this->insert($cols);
        $expect = 11;
        $actual = $this->pdo->lastInsertId();
        $this->assertEquals($expect, $actual);
    }
    
    public function testTransactions()
    {
        // data
        $cols = array('name' => 'Laura');
    
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
    
    public function testProfiling()
    {
        $this->pdo->setProfiler(new Profiler);
        
        // leave inactive
        $this->pdo->query("SELECT 1 FROM pdotest");
        $profiles = $this->pdo->getProfiler()->getProfiles();
        $this->assertEquals(0, count($profiles));
        
        // activate
        $this->pdo->getProfiler()->setActive(true);
        
        $this->pdo->query("SELECT 1 FROM pdotest");
        
        $this->pdo->exec("SELECT 2 FROM pdotest");
        
        $this->pdo->fetchAll("SELECT 3 FROM pdotest", array('zim' => 'gir'));
        
        $profiles = $this->pdo->getProfiler()->getProfiles();
        $this->assertEquals(3, count($profiles));
        
        // get the profiles, remove stuff that's variable
        $actual = $this->pdo->getProfiler()->getProfiles();
        foreach ($actual as $key => $val) {
            unset($actual[$key]['duration']);
            unset($actual[$key]['trace']);
        }
        
        $expect = array(
            0 => array(
                'function' => 'query',
                'statement' => 'SELECT 1 FROM pdotest',
                'bind_values' => array(),
            ),
            1 => array(
                'function' => 'exec',
                'statement' => 'SELECT 2 FROM pdotest',
                'bind_values' => array(),
            ),
            2 => array(
                'function' => 'perform',
                'statement' => 'SELECT 3 FROM pdotest',
                'bind_values' => array(
                    'zim' => 'gir',
                ),
            ),
        );
        
        $this->assertSame($expect, $actual);
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
        $val = array(
            1 => '-1',
            'max' => '99',
        );
        $res = $this->pdo->fetchAll($stm, $val);
        $this->assertSame(10, count($res));
    }
    
    public function testNumberedPlaceholderArray()
    {
        $stm = 'SELECT * FROM pdotest WHERE id IN (?)';
        $val = array(
            1 => array('1', '2', '3'),
        );
        $res = $this->pdo->fetchAll($stm, $val);
        $this->assertSame(3, count($res));
    }
    
    public function testPdoDependency()
    {
        // pass in ExtendedPdo to see if it can replace PDO
        $depend = new PdoDependent($this->pdo);
        $actual = $depend->fetchAll();
        $expect = array();
        foreach ($this->data as $id => $name) {
            $expect[] = array(
                'id' => $id,
                'name' => $name
            );
        }
        $this->assertEquals($expect, $actual);
    }
}
