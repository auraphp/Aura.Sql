<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;

/**
 *
 * Manages PDO connection objects for default, read, and write connections.
 *
 * @package aura/sql
 *
 */
class ConnectionLocator implements ConnectionLocatorInterface
{
    /**
     *
     * A registry of PDO connection entries.
     *
     * @var array
     *
     */
    protected $default;

    /**
     *
     * A registry of PDO connection entries.
     *
     * @var array
     *
     */
    protected $read = [];

    /**
     *
     * A registry of PDO connection entries.
     *
     * @var array
     *
     */
    protected $write = [];

    /**
     *
     * Constructor.
     *
     * @param callable $default A callable to create a default connection.
     *
     * @param array $read An array of callables to create read connections.
     *
     * @param array $write An array of callables to create write connections.
     *
     */
    public function __construct(
        $default = null,
        array $read = array(),
        array $write = array()
    ) {
        if ($default) {
            $this->setDefault($default);
        }
        foreach ($read as $name => $callable) {
            $this->setRead($name, $callable);
        }
        foreach ($write as $name => $callable) {
            $this->setWrite($name, $callable);
        }
    }

    /**
     *
     * Sets the default connection registry entry.
     *
     * @param callable $callable The registry entry.
     *
     * @return null
     *
     */
    public function setDefault(callable $callable)
    {
        $this->default = $callable;
    }

    /**
     *
     * Returns the default connection object.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getDefault()
    {
        if (! $this->default instanceof ExtendedPdo) {
            $this->default = call_user_func($this->default);
        }

        return $this->default;
    }

    /**
     *
     * Sets a read connection registry entry by name.
     *
     * @param string $name The name of the registry entry.
     *
     * @param callable $callable The registry entry.
     *
     * @return null
     *
     */
    public function setRead($name, callable $callable)
    {
        $this->read[$name] = $callable;
    }

    /**
     *
     * Returns a read connection by name; if no name is given, picks a
     * random connection; if no read connections are present, returns the
     * default connection.
     *
     * @param string $name The read connection name to return.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getRead($name = null)
    {
        return $this->getConnection('read', $name);
    }

    /**
     *
     * Sets a write connection registry entry by name.
     *
     * @param string $name The name of the registry entry.
     *
     * @param callable $callable The registry entry.
     *
     * @return null
     *
     */
    public function setWrite($name, callable $callable)
    {
        $this->write[$name] = $callable;
    }

    /**
     *
     * Returns a write connection by name; if no name is given, picks a
     * random connection; if no write connections are present, returns the
     * default connection.
     *
     * @param string $name The write connection name to return.
     *
     * @return ExtendedPdoInterface
     *
     */
    public function getWrite($name = null)
    {
        return $this->getConnection('write', $name);
    }

    /**
     *
     * Returns a connection by name.
     *
     * @param string $type The connection type ('read' or 'write').
     *
     * @param string $name The name of the connection.
     *
     * @return ExtendedPdoInterface
     *
     * @throws Exception\ConnectionNotFound
     *
     */
    protected function getConnection($type, $name)
    {
        $conn = &$this->{$type};

        if (empty($conn)) {
            return $this->getDefault();
        }

        if (! $name) {
            $name = array_rand($conn);
        }

        if (! isset($conn[$name])) {
            throw new Exception\ConnectionNotFound("{$type}:{$name}");
        }

        if (! $conn[$name] instanceof ExtendedPdo) {
            $conn[$name] = call_user_func($conn[$name]);
        }

        return $conn[$name];
    }
}
