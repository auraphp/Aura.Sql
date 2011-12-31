<?php
/**
 * Package prefix for autoloader.
 */
$loader->add('Aura\Sql\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

$di->params['Aura\Sql\ConnectionFactory'] = [
    'forge' => $di->getForge(),
    'map'   => [
        'mysql'         => 'Aura\Sql\Connection\Mysql',
        'sqlsrv'        => 'Aura\Sql\Connection\Sqlsrv',
        'sqlsrv_denali' => 'Aura\Sql\Connection\SqlsrvDenali',
    ],
];

$di->params['Aura\Sql\ConnectionManager'] = [
    'factory' => $di->lazyNew('Aura\Sql\ConnectionFactory'),
    'default' => [
        'adapter'  => 'mysql',
        'dsn'      => [
            'host' => 'localhost',
        ],
        'username' => 'your_username',
        'password' => 'your_password',
    ],
];

$di->params['Aura\Sql\Connection\AbstractConnection'] = [
    'signal' => $di->lazyGet('signal_manager'),
];

$di->set('sql_connection_manager', function() use ($di) {
    return $di->newInstance('Aura\Sql\ConnectionManager');
});
