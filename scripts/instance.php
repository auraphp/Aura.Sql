<?php
namespace Aura\Sql\Connection;
use Aura\Sql\ConnectionFactory as ConnectionFactory;
use Aura\Sql\Select;
use Aura\Di\Forge;
use Aura\Di\Config;
use Aura\Signal\Manager as SignalManager;
use Aura\Signal\HandlerFactory as HandlerFactory;
use Aura\Signal\ResultFactory as ResultFactory;
use Aura\Signal\ResultCollection as ResultCollection;

require_once dirname(__DIR__) . '/src.php';

$forge = new Forge(new Config);
        
$signal_manager = new SignalManager(new HandlerFactory, new ResultFactory, new ResultCollection);

//Type of connection, mysql , sqlsrv or sqlsrv_denali
$type = 'mysql';

//Parameters like host name , username , password , additional things for utf-8 characterset
$params = array(
    'dsn' => array(
        'host' => 'localhost',
        'dbname' => 'test',
    ),
    'username' => 'root',
    'password' => 'admin',
    'options' => array(),
);

$params['signal'] = $signal_manager;

$factory = new ConnectionFactory($forge, array(
    'mysql'         => 'Aura\Sql\Connection\Mysql',
    'sqlsrv'        => 'Aura\Sql\Connection\Sqlsrv',
    'sqlsrv_denali' => 'Aura\Sql\Connection\SqlsrvDenali',
));

//return connection
return $factory->newInstance($type, $params);
