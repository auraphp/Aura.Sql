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
 * MysqlTest
 */
$GLOBALS['Aura\Sql\Driver\MysqlTest']['connect_params'] = [
    'dsn' => [
        'host' => 'localhost',
        'dbname' => 'test',
    ],
    'username' => 'root',
    'password' => 'admin',
    'options' => [],
];

$GLOBALS['Aura\Sql\Driver\MysqlTest']['expect_dsn_string'] = 'mysql:host=localhost;dbname=test';

/**
 * SqlsrvTest
 */
$GLOBALS['Aura\Sql\Driver\SqlsrvTest']['connect_params'] = [
    'dsn' => [
        'Server' => 'localhost\\SQLEXPRESS',
        'Database' => 'test',
    ],
    'username' => 'sa',
    'password' => 'JIC2011@MS',
    'options' =>  [],
];
    
$GLOBALS['Aura\Sql\Driver\SqlsrvTest']['expect_dsn_string'] = 'sqlsrv:Server=localhost\\SQLEXPRESS;Database=test';

/**
 * Sqlite
 */
$GLOBALS['Aura\Sql\Driver\SqliteTest']['connect_params'] = [
    'dsn' => ':memory:',
];
    
$GLOBALS['Aura\Sql\Driver\SqliteTest']['expect_dsn_string'] = 'sqlite::memory:';
