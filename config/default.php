<?php
$di->params['Aura\Sql\ConnectionFactory'] = array(
    'forge' => $di->getForge(),
    'map'   => array(
        'mysql'         => 'Aura\Sql\Connection\Mysql',
        'sqlsrv'        => 'Aura\Sql\Connection\Sqlsrv',
        'sqlsrv_denali' => 'Aura\Sql\Connection\SqlsrvDenali',
    ),
);

$di->params['Aura\Sql\ConnectionManager'] = array(
    'factory' => $di->lazyNew('Aura\Sql\ConnectionFactory'),
    'default' => array(
        'adapter'  => 'mysql',
        'dsn'      => array(
            'host' => 'localhost',
        ),
        'username' => 'your_username',
        'password' => 'your_password',
    ),
);

$di->params['Aura\Sql\Connection\AbstractConnection'] = array(
    'signal' => $di->lazyGet('signal_manager'),
);

$di->set('sql_connection_manager', function() use ($di) {
    return $di->newInstance('Aura\Sql\ConnectionManager');
});
