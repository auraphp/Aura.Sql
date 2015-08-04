<?php
namespace Aura\Sql;


class FakePgsqlPDO extends \PDO
{
    public function getAttribute ($attribute)
    {
        if ($attribute === \PDO::ATTR_DRIVER_NAME) {
            return 'pgsql';
        }
        return parent::getAttribute($attribute);
    }
}