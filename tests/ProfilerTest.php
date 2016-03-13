<?php
namespace Aura\Sql;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

class ProfilerTest extends AbstractExtendedPdoTest
{
    /** @var TestHandler  */
    private $log;

    /** @var Logger  */
    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->log = new TestHandler();
        $this->logger = new Logger(__CLASS__);
        $this->logger->pushHandler($this->log);
    }

    protected function newExtendedPdo()
    {
        return new ProfiledExtendedPdo('sqlite::memory:');
    }

    public function testGetPdo()
    {
        $lazy_pdo = $this->pdo->getPdo();
        $this->assertInstanceOf('PDO', $lazy_pdo);
    }

    public function testEnableLogging()
    {
        $this->pdo->setLogger($this->logger);

        // leave inactive
        $this->pdo->query("SELECT 1 FROM pdotest");
        $this->assertEquals(0, count($this->log->getRecords()), 'should log nothing: not enabled yet');

        // activate
        $this->pdo->enableLogging(true);
        $this->pdo->setMessagePrefix($prefix = 'My Connection');

        $this->pdo->query("SELECT 1 FROM pdotest");
        $this->pdo->exec("SELECT 2 FROM pdotest");
        $this->pdo->fetchAll("SELECT 3 FROM pdotest", array('zim' => 'gir'));
        // 1 x query(), 1 x exec(), and 2 x fetchAll() which is executed as prepare() and perform()
        $this->assertEquals(4, count($this->log->getRecords()));

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

        foreach ($this->log->getRecords() as $index => $entry) {
            $this->assertRegExp('|' . $prefix . '.*' . $expect[$index]['function'] . '|', $entry['message'], $index);
            $this->assertEquals($prefix, $entry['context']['context'], $index);
            $this->assertEquals($expect[$index]['function'], $entry['context']['function'], $index);
            $this->assertEquals($expect[$index]['statement'], $entry['context']['statement'], $index);
            $this->assertEquals($expect[$index]['bind_values'], $entry['context']['values'], $index);
            $this->assertGreaterThan(0, $entry['context']['duration'], $index);
            $this->assertGreaterThan(0, $entry['context']['start_time'], $index);
            $this->assertGreaterThan($entry['context']['start_time'], $entry['context']['finish_time'], $index);
        }

        // de-activate
        $this->pdo->enableLogging(false);
        $this->assertEquals(4, count($this->log->getRecords()));
        $this->pdo->query("SELECT 5 FROM pdotest");
        $this->assertEquals(4, count($this->log->getRecords()));
    }
}
