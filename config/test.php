<?php
/**
 * Package prefix for autoloader.
 */
$loader->add('Aura\Sql\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests');

/**
 * MysqlTest
 */
$GLOBALS['Aura\Sql\Connection\MysqlTest']['connect_params'] = array(
    'dsn' => array(
        'host' => 'localhost',
        'dbname' => 'test',
    ),
    'username' => 'root',
    'password' => 'admin',
    'options' => array(),
);

$GLOBALS['Aura\Sql\Connection\MysqlTest']['expect_dsn_string'] = 'mysql:host=localhost;dbname=test';

/**
 * SqlsrvTest
 */
$GLOBALS['Aura\Sql\Connection\SqlsrvTest']['connect_params'] = array(
    'dsn' => array(
        'Server' => 'localhost\\SQLEXPRESS',
        'Database' => 'test',
    ),
    'username' => 'sa',
    'password' => 'JIC2011@MS',
    'options' =>  array(),
);
    
$GLOBALS['Aura\Sql\Connection\SqlsrvTest']['expect_dsn_string'] = 'sqlsrv:Server=localhost\\SQLEXPRESS;Database=test';

/**
 * SqlsrvDenaliTest
 */
$GLOBALS['Aura\Sql\Connection\SqlsrvDenaliTest']['connect_params'] = array(
    'dsn' => array(
        'Server' => 'jic-31.jic',
        'Database' => 'ZF',
    ),
    'username' => 'sa',
    'password' => 'JIC2011@MS',
    'options' =>  array(),
);
    
$GLOBALS['Aura\Sql\Connection\SqlsrvDenaliTest']['expect_dsn_string'] = 'sqlsrv:Server=jic-31.jic;Database=ZF';
