<?php
namespace Aura\Sql\Driver;
class MockDriver
{
    protected $params = [];
    
    // skip the signal manager, otherwise mimic the AbstractDriver params
    public function __construct(
        array $dsn,
        $username,
        $password,
        array $options
    ) {
        $this->params = [
            'dsn'      => $dsn,
            'username' => $username,
            'password' => $password,
            'options'  => $options,
        ];
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
