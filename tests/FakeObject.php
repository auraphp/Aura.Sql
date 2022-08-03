<?php
namespace Aura\Sql;

use stdClass;

class FakeObject extends stdClass
{
    public $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}
