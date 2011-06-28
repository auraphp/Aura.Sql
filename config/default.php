<?php
$di->params['Aura\Sql\ConnectionFactory']['map'] = array(
    'mysql'         => 'Aura\Sql\Connection\Mysql',
    'sqlsrv'        => 'Aura\Sql\Connection\Sqlsrv',
    'sqlsrv_denali' => 'Aura\Sql\Connection\SqlsrvDenali',
);

$di->params['Aura\Sql\ConnectionManager']['default'] = array(
    'type'     => 'mysql',
    'dsn'      => array(
        'host' => 'localhost',
    ),
    'username' => 'your_username',
    'password' => 'your_password',
);
