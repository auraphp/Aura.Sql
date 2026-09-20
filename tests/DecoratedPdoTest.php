<?php
namespace Aura\Sql;

use PDO;

class DecoratedPdoTest extends ExtendedPdoTest
{
    private $innerPdo;

    protected function newPdo()
    {
        return new DecoratedPdo($this->innerPdo = new PDO('sqlite::memory:'));
    }

    public function testDisconnect()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->expectException(Exception\CannotDisconnect::CLASS);
        $this->pdo->disconnect();
    }

    public function testGetPdo()
    {
        $this->assertTrue($this->pdo->isConnected());
        $this->assertSame($this->innerPdo, $this->pdo->getPdo());
    }

    public function testConnectKeepsPasswordOutOfStackTrace()
    {
        // Without this, PHP records no arguments at all and the test would
        // pass even when the password is left unredacted.
        $ignore_args = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');

        $password = 'sekret42';
        $username = 'a-recorded-username';

        try {
            DecoratedPdo::connect('sqlite:/nonexistent-directory/test.db', $username, $password);
            $this->fail('Expected the connection to fail.');
        } catch (\PDOException $e) {
            $args = [];
            foreach ($e->getTrace() as $frame) {
                foreach ($frame['args'] ?? [] as $arg) {
                    if (is_string($arg)) {
                        $args[] = $arg;
                    }
                }
            }

            // Guards against the assertions below passing vacuously because
            // no arguments were recorded in the first place.
            $this->assertContains($username, $args);

            foreach ($args as $arg) {
                $this->assertStringNotContainsString($password, $arg);
            }
        } finally {
            ini_set('zend.exception_ignore_args', $ignore_args);
        }
    }
}
