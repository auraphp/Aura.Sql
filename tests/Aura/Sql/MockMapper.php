<?php
namespace Aura\Sql;

class MockMapper extends AbstractMapper
{
    protected $table = 'fake_table';
    
    protected $cols_fields = [
        'id'         => 'identity',
        'name_first' => 'firstName',
        'name_last'  => 'lastName',
    ];
    
    protected $primary_col = 'id';
    
    protected $identity_field = 'identity';
}
