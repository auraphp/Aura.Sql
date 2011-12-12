<?php
namespace Aura\Sql\Connection;
class MockConnection
{
    protected $params = array();
    
    // skip the signal manager, otherwise mimic the AbstractConnection params
    public function __construct(
        array $dsn,
        $username,
        $password,
        array $options
    ) {
        $this->params = array(
            'dsn'      => $dsn,
            'username' => $username,
            'password' => $password,
            'options'  => $options,
        );
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getDsnHost()
    {
        return $this->params['dsn']['host'];
    }
}
