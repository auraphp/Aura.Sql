<?php
/**
 * Mysql
 */
$GLOBALS['Aura\Sql\Connection\MysqlTest']['connection_params'] = [
    'dsn' => [
        'host' => 'localhost',
    ],
    'username' => 'root',
    'password' => '',
    'options' => [],
];

$GLOBALS['Aura\Sql\Connection\MysqlTest']['expect_dsn_string'] = 'mysql:host=localhost';

/**
 * Pgsql
 */
$GLOBALS['Aura\Sql\Connection\PgsqlTest']['connection_params'] = [
    'dsn' => [
        'host' => 'localhost',
        'dbname' => 'test',
    ],
    'username' => 'postgres',
    'password' => '',
    'options' => [],
];

$GLOBALS['Aura\Sql\Connection\PgsqlTest']['expect_dsn_string'] = 'pgsql:host=localhost;dbname=test';

/**
 * Sqlite
 */
$GLOBALS['Aura\Sql\Connection\SqliteTest']['connection_params'] = [
    'dsn' => ':memory:',
];
    
$GLOBALS['Aura\Sql\Connection\SqliteTest']['expect_dsn_string'] = 'sqlite::memory:';
