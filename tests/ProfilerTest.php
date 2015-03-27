<?php
namespace Aura\Sql;

use Aura\Sql\AbstractExtendedPdoTest;

class ProfilerTest extends AbstractExtendedPdoTest
{
    protected function newExtendedPdo()
    {
        return new ExtendedPdo('sqlite::memory:');
    }

    public function testGetPdo()
    {
        $lazy_pdo = $this->pdo->getPdo();
        $this->assertInstanceOf('PDO', $lazy_pdo);
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
        // 1 x query(), 1 x exec(), and 2 x fetchAll() which is executed as prepare() and perform()
        $this->assertEquals(4, count($profiles));

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
                'function' => 'prepare',
                'statement' => 'SELECT 3 FROM pdotest',
                'bind_values' => array(),
            ),
            3 => array(
                'function' => 'perform',
                'statement' => 'SELECT 3 FROM pdotest',
                'bind_values' => array(
                    'zim' => 'gir',
                ),
            ),
        );

        $this->assertSame($expect, $actual);
    }

    public function testResetProfiles()
    {
        $this->pdo->setProfiler(new Profiler);

        // leave inactive
        $this->pdo->query("SELECT 1 FROM pdotest");
        $profiles = $this->pdo->getProfiler()->getProfiles();
        $this->assertEquals(0, count($profiles));

        // activate
        $this->pdo->getProfiler()->setActive(true);

        $this->pdo->query("SELECT 1 FROM pdotest");

        $profiles = $this->pdo->getProfiler()->getProfiles();
        $this->assertEquals(1, count($profiles));

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
            )
        );

        $this->assertSame($expect, $actual);

        $this->pdo->getProfiler()->resetProfiles();

        $this->pdo->exec("SELECT 2 FROM pdotest");

        $this->pdo->fetchAll("SELECT 3 FROM pdotest", array('zim' => 'gir'));

        $profiles = $this->pdo->getProfiler()->getProfiles();
        // fetchAll() is executed as prepare() and perform()
        $this->assertEquals(3, count($profiles));

        // get the profiles, remove stuff that's variable
        $actual = $this->pdo->getProfiler()->getProfiles();
        foreach ($actual as $key => $val) {
            unset($actual[$key]['duration']);
            unset($actual[$key]['trace']);
        }

        $expect = array(
            0 => array(
                'function' => 'exec',
                'statement' => 'SELECT 2 FROM pdotest',
                'bind_values' => array(),
            ),
            1 => array(
                'function' => 'prepare',
                'statement' => 'SELECT 3 FROM pdotest',
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
        $this->pdo->getProfiler()->resetProfiles();
        $profiles = $this->pdo->getProfiler()->getProfiles();
        $this->assertEquals(0, count($profiles));
    }
}
