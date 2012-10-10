<?php
/**
 * Mysql
 */
$GLOBALS['Aura\Sql\Connection\MysqlTest']['connection_params'] = [
    'dsn' => [
        'host' => '127.0.0.1',
    ],
    'username' => 'root',
    'password' => '',
    'options' => [],
];

$GLOBALS['Aura\Sql\Connection\MysqlTest']['expect_dsn_string'] = 'mysql:host=127.0.0.1';

$GLOBALS['Aura\Sql\Connection\MysqlTest']['db_setup_class'] = 'Aura\Sql\DbSetup\Mysql';

/**
 * Pgsql
 */
$GLOBALS['Aura\Sql\Connection\PgsqlTest']['connection_params'] = [
    'dsn' => [
        'host' => '127.0.0.1',
        'dbname' => 'test',
    ],
    'username' => 'postgres',
    'password' => '',
    'options' => [],
];

$GLOBALS['Aura\Sql\Connection\PgsqlTest']['expect_dsn_string'] = 'pgsql:host=127.0.0.1;dbname=test';

$GLOBALS['Aura\Sql\Connection\PgsqlTest']['db_setup_class'] = 'Aura\Sql\DbSetup\Pgsql';

/**
 * Sqlite
 */
$GLOBALS['Aura\Sql\Connection\SqliteTest']['connection_params'] = [
    'dsn' => ':memory:',
];
    
$GLOBALS['Aura\Sql\Connection\SqliteTest']['expect_dsn_string'] = 'sqlite::memory:';

$GLOBALS['Aura\Sql\Connection\SqliteTest']['db_setup_class'] = 'Aura\Sql\DbSetup\Sqlite';
