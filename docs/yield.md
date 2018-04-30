# The `yield*()` Methods

_ExtendedPdo_ comes with `yield*()` methods to help reduce memory usage. Whereas
many `fetch*()` methods collect all the query result rows before returning them
all at once, the equivalent `yield*()` methods generate one result row at a
time. For example:

```php
$stm  = 'SELECT * FROM test WHERE foo = :foo AND bar = :bar';
$bind = array('foo' => 'baz', 'bar' => 'dib');

// like fetchAll(), each row is an associative array
foreach ($pdo->yieldAll($stm, $bind) as $row) {
    // ...
}

// like fetchAssoc(), each key is the first column,
// and the row is an associative array
foreach ($pdo->yieldAssoc($stm, $bind) as $key => $row) {
    // ...
}

// like fetchCol(), each result is a value from the first column
foreach ($pdo->yieldCol($stm, $bind) as $val) {
    // ...
}

// like fetchObjects(), each result is an object; pass an optional
// class name and optional array of constructor arguments.
$class = 'ClassName';
$args = ['arg0', 'arg1', 'arg2'];
foreach ($pdo->yieldObjects($stm, $bind, $class, $args) as $object) {
    // ...
}

// like fetchPairs(), each result is a key-value pair from the
// first and second columns
foreach ($pdo->yieldPairs($stm, $bind) as $key => $val) {
    // ...
}
```
