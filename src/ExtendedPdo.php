<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql;

use Aura\Sql\Exception;
use PDO;
use PDOStatement;

/**
 *
 * This extended decorator for PDO provides lazy connection, array quoting, a
 * new `perform()` method, and new `fetch*()` methods.
 *
 * @package Aura.Sql
 *
 */
class ExtendedPdo extends DecoratedPdo implements ExtendedPdoInterface
{
    /**
     *
     * The attributes for a lazy connection.
     *
     * @var array
     *
     */
    protected $attributes = array(
        self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION,
    );

    /**
     *
     * The DSN for a lazy connection.
     *
     * @var string
     *
     */
    protected $dsn;

    /**
     *
     * PDO options for a lazy connection.
     *
     * @var array
     *
     */
    protected $options = [];

    /**
     *
     * The password for a lazy connection.
     *
     * @var string
     *
     */
    protected $password;

    /**
     *
     * The username for a lazy connection.
     *
     * @var string
     *
     */
    protected $username;

    /**
     *
     * This constructor is pseudo-polymorphic. You may pass a normal set of PDO
     * constructor parameters, and ExtendedPdo will use them for a lazy
     * connection. Alternatively, if the `$dsn` parameter is an existing PDO
     * instance, that instance will be decorated by ExtendedPdo; the remaining
     * parameters will be ignored.
     *
     * @param PDO|string $dsn The data source name for a lazy PDO connection,
     * or an existing instance of PDO. If the latter, the remaining params are
     * ignored.
     *
     * @param string $username The username for a lazy connection.
     *
     * @param string $password The password for a lazy connection.
     *
     * @param array $options Driver-specific options for a lazy connection.
     *
     * @param array $attributes Attributes to set after a lazy connection.
     *
     * @see http://php.net/manual/en/pdo.construct.php
     *
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = [],
        array $attributes = []
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     *
     * Connects to the database and sets PDO attributes.
     *
     * @return null
     *
     * @throws \PDOException if the connection fails.
     *
     */
    public function connect()
    {
        // don't connect twice
        if ($this->pdo) {
            return;
        }

        // connect to the database
        $this->beginProfile(__FUNCTION__);
        $this->pdo = new PDO(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options
        );
        $this->endProfile();

        // set attributes
        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }
    }

    /**
     *
     * Explicitly disconnect by unsetting the PDO instance; does not prevent
     * later reconnection, whether implicit or explicit.
     *
     * @return null
     *
     */
    public function disconnect()
    {
        $this->pdo = null;
    }

    /**
     *
     * Returns the DSN for a lazy connection.
     *
     * @return string
     *
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     *
     * Sets a PDO attribute value.
     *
     * @param mixed $attribute The PDO::ATTR_* constant.
     *
     * @param mixed $value The value for the attribute.
     *
     * @return bool True on success, false on failure. Note that if PDO has not
     * not connected, all calls will be treated as successful.
     *
     */
    public function setAttribute($attribute, $value)
    {
        if ($this->pdo) {
            return $this->pdo->setAttribute($attribute, $value);
        }

        $this->attributes[$attribute] = $value;
        return true;
    }
}
