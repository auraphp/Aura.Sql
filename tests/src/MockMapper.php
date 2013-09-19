<?php
namespace Aura\Sql;

class MockMapper extends AbstractMapper
{
    protected $table = 'aura_test_table';
    
    protected $cols_fields = [
        'id'                    => 'identity',
        'name'                  => 'firstName',
        'test_size_scale'       => 'sizeScale',
        'test_default_null'     => 'defaultNull',
        'test_default_string'   => 'defaultString',
        'test_default_number'   => 'defaultNumber',
        'test_default_ignore'   => 'defaultIgnore',
    ];
    
    protected $primary_col = 'id';
    
    protected $identity_field = 'identity';
}
