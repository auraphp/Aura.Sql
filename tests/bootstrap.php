<?php
spl_autoload_register(function($class) {
    $dir   = dirname(__DIR__);
    $file  = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $src = $dir . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR . $file;
    if (file_exists($src)) {
        require $src;
    }
    $tests = $dir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $file;
    if (file_exists($tests)) {
        require $tests;
    }
});

/**
 * Mysql
 */
$GLOBALS['Aura\Sql\Adapter\MysqlTest']['adapter_params'] = [
    'dsn' => [
        'host' => 'localhost',
    ],
    'username' => 'root',
    'password' => 'admin',
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
    'password' => 'postgres',
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
