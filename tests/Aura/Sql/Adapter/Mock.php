<?php
namespace Aura\Sql\Adapter;
use Aura\Sql\ProfilerInterface;
use Aura\Sql\ColumnFactory;
use Aura\Sql\SelectFactory;

class Mock extends AbstractAdapter
{
    protected $params = [];
    
    public function __construct(
        ProfilerInterface $profiler,
        ColumnFactory $column_factory,
        SelectFactory $select_factory,
        $dsn,
        $username = null,
        $password = null,
        array $options = []
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
    
    public function fetchTableList($schema = null)
    {
        return [];
    }
    
    public function fetchTableCols($spec)
    {
        return [];
    }
}
