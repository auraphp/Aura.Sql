<?php
namespace Aura\Sql\Query;

use Aura\Sql\Adapter\AbstractAdapter;

abstract class AbstractQuery
{
    /**
     * 
     * An SQL connection adapter.
     * 
     * @var AbstractAdapter
     * 
     */
    protected $sql;

    /**
     * 
     * Constructor.
     * 
     * @param AbstractAdapter $sql An SQL adapter.
     * 
     * @return void
     * 
     */
    public function __construct(AbstractAdapter $sql)
    {
        $this->sql = $sql;
    }
    
    abstract public function __toString();
    
    protected function indentCsv(array $list)
    {
        if (! $list) {
            return;
        }
        
        return PHP_EOL
             . '    ' . implode(',' . PHP_EOL . '    ', $list)
             . PHP_EOL;
    }
    
    protected function indent($list)
    {
        if (! $list) {
            return;
        }
        
        return PHP_EOL
             . '    ' . implode(PHP_EOL . '    ', $list)
             . PHP_EOL;
    }
}
