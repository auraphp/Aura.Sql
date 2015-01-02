<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Sql
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Sql;

/**
 * This file offers a callable configuration object, to avoid creating the PDO connection
 * until we need it. Replaces a Closure, in case we ever want to serialize the configuration.
 */
class CallableConfig
{
    /**
     * A DSN compatible with PDO.
     *
     * @var string
     */
    protected $dsn;

    /**
     * The database username.
     *
     * @var null|string
     */
    protected $username;

    /**
     * The database password.
     *
     * @var null|string
     */
    protected $password;

    /**
     * The PDO options array.
     *
     * @var array
     */
    protected $options = array();

    /**
     * The PDO attributes array.
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * @param $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     * @param array $attributes
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = array(),
        array $attributes = array()
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     * @return ExtendedPdo
     */
    public function __invoke()
    {
        return new ExtendedPdo(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options,
            $this->attributes
        );
    }
}