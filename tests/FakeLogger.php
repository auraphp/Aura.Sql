<?php
namespace Aura\Sql;

use Psr\Log\AbstractLogger;

class FakeLogger extends AbstractLogger
{
    protected $log = [];

    public function getLog()
    {
        return $this->log;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        $this->log[] = compact('level', 'message', 'context');
    }
}
