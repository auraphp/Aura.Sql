<?php
namespace Aura\Sql;
class ColumnFactory
{
    public function newInstance(
        $name,
        $type, 
        $size,
        $scope,
        $notnull,
        $default,
        $autoinc,
        $primary
    ) {
        return new Column(
            $name,
            $type, 
            $size,
            $scope,
            $notnull,
            $default,
            $autoinc,
            $primary
        );
    }
}
