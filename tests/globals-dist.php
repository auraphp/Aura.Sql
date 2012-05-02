<?php
/**
 * Mysql
 */
$GLOBALS['Aura\Sql\Adapter\MysqlTest']['adapter_params'] = [
    'dsn' => [
        'host' => 'localhost',
    ],
    'username' => 'root',
    'password' => '',
    'options' => [],
];

$GLOBALS['Aura\Sql\Adapter\MysqlTest']['expect_dsn_string'] = 'mysql:host=localhost';

/**
 * Pgsql
 */
$GLOBALS['Aura\Sql\Adapter\PgsqlTest']['adapter_params'] = [
    'dsn' => [
        'host' => 'localhost',
        'dbname' => 'test',
    ],
    'username' => 'postgres',
    'password' => '',
    'options' => [],
];

$GLOBALS['Aura\Sql\Adapter\PgsqlTest']['expect_dsn_string'] = 'pgsql:host=localhost;dbname=test';

/**
 * Sqlite
 */
$GLOBALS['Aura\Sql\Adapter\SqliteTest']['adapter_params'] = [
    'dsn' => ':memory:',
];
    
$GLOBALS['Aura\Sql\Adapter\SqliteTest']['expect_dsn_string'] = 'sqlite::memory:';
