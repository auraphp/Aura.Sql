<?php
namespace Aura\Sql\Connection;
use Aura\Sql\ProfilerInterface;
use Aura\Sql\ColumnFactory;
use Aura\Sql\Query\Factory as QueryFactory;

class Mock extends AbstractConnection
{
    protected $params = [];
    
    public function __construct(
        ProfilerInterface $profiler,
        ColumnFactory $column_factory,
        QueryFactory $query_factory,
        $dsn,
        $username = null,
        $password = null,
        array $options = []
    ) {
        parent::__construct(
            $profiler,
            $column_factory,
            $query_factory,
            $dsn,
            $username,
            $password,
            $options
        );
        
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
    
    public function fetchTableList($schema = null)
    {
        return [];
    }
    
    public function fetchTableCols($spec)
    {
        return [];
    }
}
