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

interface PdoInterface
{
    // parent PDO methods
    public function beginTransaction();
    public function commit();
    public function errorCode();
    public function errorInfo();
    public function exec($statement);
    public function getAttribute($attribute);
    public static function getAvailableDrivers();
    public function inTransaction();
    public function lastInsertId($name = null);
    public function prepare($statment, $driver_options = null);
    public function query($statement, $fetch_mode = null, $fetch_arg1 = null, $fetch_arg2 = null);
    public function quote($string, $parameter_type = Pdo::PARAM_STR);
    public function rollBack();
    public function setAttribute($attribute, $value);

    // extended methods
    public function connect();
    public function isConnected();
    public function bindValues(array $values);
    public function getBindValues();
    public function getDsn();
    public function fetchAll($statement, array $values = array());
    public function fetchCol($statement, array $values = array());
    public function fetchValue($statement, array $values = array());
    public function fetchAssoc($statement, array $values = array());
    public function fetchPairs($statement, array $values = array());
    public function fetchOne($statement, array $values = array());
    public function getProfiler();
    public function setProfiler(ProfilerInterface $profiler);
    public function quoteInto($string, $values);
    public function quoteName($name);
    public function quoteNamesIn($string);
}
