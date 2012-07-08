<?php
namespace Aura\Sql;
use Aura\Sql\Adapter\AbstractAdapter;

class SelectFactory
{
    public function newInstance(AbstractAdapter $sql)
    {
        return new Select($sql);
    }
}
 