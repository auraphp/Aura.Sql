# Aura.Sql

Provides an extension to the native [PDO](http://php.net/PDO) along with a
profiler and connection locator. Because _ExtendedPdo_ is an extension of the
native _PDO_, code already using the native _PDO_ or typehinted to the native
_PDO_ can use _ExtendedPdo_ without any changes.

Added functionality in _Aura.Sql_ over the native _PDO_ includes:

- **Lazy connection.** _ExtendedPdo_ connects to the database only on
  method calls that require a connection. This means you can create an
  instance and not incur the cost of a connection if you never make a query.

- **Bind values.** You may provide values for binding to the next query using
  `bindValues()`. Multiple calls to `bindValues()` will merge, not reset, the
  values. The values will be reset after calling `query()`, `exec()`,
  `prepare()`, or any of the `fetch*()` methods.  In addition, binding values
  that do not have any corresponding placeholders will not cause an error.

- **Array quoting.** The `quote()` method will accept an array as input, and
  return a string of comma-separated quoted values. In addition, named
  placeholders in prepared statements that are bound to array values will
  be replaced with comma-separated quoted values. This means you can bind
  an array of values to a placeholder used with an `IN (...)` condition.

- **Fetch methods.** _ExtendedPdo_ provides several `fetch*()` methods for
  commonly-used fetch styles. For example, you can call `fetchAll()` directly
  on the instance instead of having to prepare a statement, bind values,
  execute, and then fetch from the prepared statement. All of the `fetch*()`
  methods take an array of values to bind to to the query statement.

- **Exceptions by default.** _ExtendedPdo_ starts in the `ERRMODE_EXCEPTION`
  mode for error reporting instead of the `ERRMODE_SILENT` mode.

- **Profiler.** An optional query profiler is provided, along with an
  interface for other implementations.

- **Connection locator.** A optional lazy-loading service locator is provided
  for picking different database connections (default, read, and write).


## Foreword

### Requirements

This library requires PHP 5.3 or later, and has no userland dependencies.

### Installation

This library is installable and autoloadable via Composer with the following
`require` element in your `composer.json` file:

    "require": {
        "aura/sql": "2.*@dev"
    }
    
Alternatively, download or clone this repository, then require or include its
_autoload.php_ file.

### Tests

[![Build Status](https://api.travis-ci.org/auraphp/Aura.Sql.png?branch=develop-2)](https://travis-ci.org/auraphp/Aura.Sql)

This library has 100% code coverage with [PHPUnit][]. To run the tests at the
command line, go to the _tests_ directory and issue `phpunit`.

[phpunit]: http://phpunit.de/manual/

### PSR Compliance

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md


## Getting Started

### Instantiation

Instantiation is the same as with the native _PDO_ class: pass a data source
name, username, password, and driver options. There is one additional
parameter that allows you to pass attributes to be set after the connection is
made.

```php
<?php
use Aura\Sql\ExtendedPdo;

$pdo = new ExtendedPdo(
    'mysql:host=localhost;dbname=test',
    'username',
    'password',
    array(), // driver options as key-value pairs
    array()  // attributes as key-value pairs
);
?>
```

You can now use the _ExtendedPdo_ instance in exactly the same way as a native
_PDO_ instance.

### Lazy Connection

Whereas the native _PDO_ connects on instantiation, _ExtendedPdo_ does not
connect immediately. Instead, it connects only when you call a method that
actually needs the connection to the database; e.g., on `query()`.

If you want to force a connection, call the `connect()` method.

```php
<?php
// does not connect to the database
$pdo = new ExtendedPdo(
    'mysql:host=localhost;dbname=test',
    'username',
    'password'
);

// automatically connects
$pdo->exec('SELECT * FROM test');

// explicitly forces a connection
$pdo->connect();
?>
```

### Bind Values

Instead of having to bind values to a prepared _PDOStatement_, you can call
`bindValues()` directly on the _ExtendedPdo_ instance, and those values will
be bound to named placeholders in the next query.

```php
<?php
// the native PDO way
$pdo = new PDO(...);
$sth = $pdo->prepare('SELECT * FROM test WHERE foo = :foo AND bar = :bar');
$sth->bindValue('foo', 'foo_value');
$sth->bindValue('bar', 'bar_value');
$sth->execute();

// the ExtendedPdo way
$pdo = new ExtendedPdo(...);
$pdo->bindValues(array(
    'foo' => 'foo_value',
    'bar' => 'bar_value',
));
$sth = $pdo->query('SELECT * FROM test WHERE foo = :foo AND bar = :bar');
?>
```

It also works with sequential question-mark placeholders; note that
question-mark placeholders are numbered starting from 1, and that the keys
must be integers (not string numerics).

```php
<?php
$pdo = new ExtendedPdo(...);
$pdo->bindValues(array(
    1 => 'foo_value',
    2 => 'bar_value',
));
$sth = $pdo->query('SELECT * FROM test WHERE foo = ? AND bar = ?');
?>
```

Mixing named placeholders and question-mark placeholders is allowed:

```php
<?php
$pdo = new ExtendedPdo(...);
$pdo->bindValues(array(
    'foo' => 'foo_value',
    1 => 'bar_value',
));
$sth = $pdo->query('SELECT * FROM test WHERE foo = :foo AND bar = ?');
?>
```

### Array Quoting

The native _PDO_ `quote()` method will not quote arrays. This makes it
difficult to bind an array to something like an `IN (...)` condition in SQL.
However, _ExtendedPdo_ recognizes arrays and converts them into
comma-separated quoted strings.

```php
<?php
// the array to be quoted
$array = array('foo', 'bar', 'baz');

// the native PDO way:
// "Warning:  PDO::quote() expects parameter 1 to be string, array given"
$pdo = new PDO(...);
$cond = 'IN (' . $pdo->quote($array) . ')';

// the ExtendedPdo way:
// "IN ('foo', 'bar', 'baz')"
$pdo = new ExtendedPdo(...);
$cond = 'IN (' . $pdo->quote($array) . ')'; 
?>
```

Whereas the native _PDO_ `prepare()` does not deal with bound array values,
_ExtendedPdo_ modifies the query string to replace the named placeholder with
the quoted array.  Note that this is *not* the same thing as binding proper;
the query string itself is modified before passing to the database for value
binding.

```php
<?php
// the array to be quoted
$array = array('foo', 'bar', 'baz');

// the statement to prepare
$stm = 'SELECT * FROM test WHERE foo IN (:foo) AND bar = :bar'

// the native PDO way does not work (PHP Notice:  Array to string conversion)
$pdo = new ExtendedPdo(...);
$sth = $pdo->prepare($stm);
$sth->bindValue('foo', $array);

// the ExtendedPdo way quotes the array and replaces the array placeholder
// directly in the query string
$pdo = new ExtendedPdo(...);
$pdo->bindValues(array(
    'foo' => array('foo', 'bar', 'baz'),
    'bar' => 'qux',
));
$sth = $pdo->prepare($stm);
echo $sth->queryString;
// the query string has been modified by ExtendedPdo to become
// "SELECT * FROM test WHERE foo IN ('foo', 'bar', 'baz') AND bar = :bar"
?>
```

Finally, note that array quoting works only on the _ExtendedPdo_ instance,
not on returned _PDOStatement_ instances.

### Fetch Methods

_ExtendedPdo_ comes with `fetch*()` methods to help reduce boilerplate code.
Instead of issuing `prepare()`, a series of `bindValue()` calls, `execute()`,
and then `fetch*()` on a _PDOStatement_, you can bind values and fetch results
in one call on _ExtendedPdo_ directly.

```php
<?php
$stm  = 'SELECT * FROM test WHERE foo = :foo AND bar = :bar';
$bind = array('foo' => 'bar', 'baz' => 'dib');

// the native PDO way to "fetch all" where the result is a sequential array
// of rows, and the row arrays are keyed on the column names
$pdo = new PDO(...);
$pdo->prepare($stm);
$stm->bindValue('foo', $bind['foo']);
$stm->bindValue('bar', $bind['bar']);
$sth = $stm->execute();
$result = $sth->fetchAll(PDO::FETCH_ASSOC);

// the ExtendedPdo way to do the same kind of "fetch all"
$pdo = new ExtendedPdo(...);
$result = $pdo->fetchAll($stm, $bind);

// fetchAssoc() returns an associative array of all rows where the key is the
// first column, and the row arrays are keyed on the column names
$result = $pdo->fetchAssoc($stm, $bind);

// fetchCol() returns a sequential array of all values in the first column
$result = $pdo->fetchCol($stm, $bind);

// fetchObject() returns the first row as an object of your choosing; the
// columns are mapped to object properties. an optional 4th parameter array
// provides constructor arguments when instantiating the object.
$result = $pdo->fetchObject($stm, $bind, 'ClassName', array('ctor_arg_1'));

// fetchObjects() returns an array of objects of your choosing; the
// columns are mapped to object properties. an optional 4th parameter array
// provides constructor arguments when instantiating the object.
$result = $pdo->fetchObjects($stm, $bind, 'ClassName', array('ctor_arg_1'));

// fetchOne() returns the first row as an associative array where the keys
// are the column names
$result = $pdo->fetchOne($stm, $bind);

// fetchPairs() returns an associative array where each key is the first
// column and each value is the second column
$result = $pdo->fetchPairs($stm, $bind);

// fetchValue() returns the value of the first row in the first column
$result = $pdo->fetchValue($stm, $bind);
?>
```

The methods `fetchAll()`, `fetchAssoc()`, `fetchCol()`, and `fetchPairs()`
take an optional third parameter, a callable, to apply to each row of the
results before returning.

```php
<?php
$result = $pdo->fetchAssoc($stm, $bind, function (&$row) {
    // add a column to the row
    $row['my_new_col'] = 'Added this column from the callable.';
});
?>
```

## Profiler

When debugging, it is often useful to see what queries have been executed,
where they were issued from in the codebase, and how long they took to
complete. _ExtendedPdo_ comes with an optional profiler that you can use to
discover that information.

```php
<?php
use Aura\Sql\ExtendedPdo;
use Aura\Sql\Profiler;

$pdo = new ExtendedPdo(...);
$pdo->setProfiler(new Profiler);

// ...
// query(), fetch(), beginTransaction()/commit()/rollback() etc.
// ...

// now retrieve the profile information:
$profiles = $pdo->getProfiler()->getProfiles();
?>
```

Each profile entry will have these keys:

- `function`: The method that was called on _ExtendedPdo_ that created the
  profile entry.

- `duration`: How long the query took to complete, in seconds.

- `statement`: The query string that was issued, if any. (Methods like
  `connect()` and `rollBack()` do not send query strings.)

- `bind_values`: Any values that were bound to the query.

- `trace`: An exception stack trace indicating where the query was issued from
  in the codebase.

Setting the _Profiler_ into the _ExtendedPdo_ instance is optional. Once it
is set, you can activate and deactivate it as you wish using the
`Profiler::setActive()` method. When not active, query profiles will not be
retained.

```php
<?php
$pdo = new ExtendedPdo(...);
$pdo->setProfiler(new Profiler);

// deactivate, issue a query, and reactivate;
// the query will not show up in the profiles
$pdo->getProfiler()->setActive(false);
$pdo->fetchAll('SELECT * FROM foo');
$pdo->getProfiler()->setActive(true);
?>
```

## Connection Locator

Frequently, high-traffic PHP applications use multiple database servers,
generally one for writes, and one or more for reads. The _ConnectionLocator_
allows you to define multiple _ExtendedPdo_ objects for lazy-loaded read and
write connections. It will create the connections only when they are when
called. The creation logic is wrapped in a callable.

First, create the _ConnectionLocator_:

```php
<?php
use Aura\Sql\ExtendedPdo;
use Aura\Sql\ConnectionLocator;

$connections = new ConnectionLocator;
?>
```

Now add a default connection; this will be used when a read or write
connection is not defined. (This is also useful for setting up connection
location in advance of actually having multiple database servers.)

```php
<?php
$connections->setDefault(function () {
    return new ExtendedPdo(
        'mysql:host=default.db.localhost;dbname=database',
        'username',
        'password',
    );
});
?>
```

Next, add as many named read and write connections as you like:

```php
<?php
// the write (master) server
$connections->setWrite('master', function () {
    return new ExtendedPdo(
        'mysql:host=master.db.localhost;dbname=database',
        'username',
        'password',
    );
});

// read (slave) #1
$connections->setRead('slave1', function () {
    return new ExtendedPdo(
        'mysql:host=slave1.db.localhost;dbname=database',
        'username',
        'password',
    );
});

// read (slave) #2
$connections->setRead('slave2', function () {
    return new ExtendedPdo(
        'mysql:host=slave2.db.localhost;dbname=database',
        'username',
        'password',
    );
});

// read (slave) #3
$connections->setRead('slave3', function () {
    return new ExtendedPdo(
        'mysql:host=slave3.db.localhost;dbname=database',
        'username',
        'password',
    );
});
?>
```

Finally, retrieve a connection from the locator when you need it. This will
create the connection (if needed) and then return it.

- `getDefault()` will return the default connection.

- `getRead()` will return a named read connection; if no name is specified, it
  will return a random read connection. If no read connections are defined, it
  will return the default connection.

- `getWrite()` will return a named write connection; if no name is specified,
  it will return a random read connection. If no write connections are
  defined, it will return the default connection.

```php
<?php
$read = $connection->getRead();
$results = $read->fetchAll('SELECT * FROM table_name LIMIT 10');
?>
```

### Construction-Time Configuration

The _ConnectionLocator_ can be configured with all its connections at 
construction time; this is useful with dependency injection mechanisms.

```php
<?php
use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;

// default connection
$default = function () {
    return new ExtendedPdo(
        'mysql:host=default.db.localhost;dbname=database',
        'username',
        'password',
    );
};

// read connections
$read = array(
    'slave1' => function () {
        return new ExtendedPdo(
            'mysql:host=slave1.db.localhost;dbname=database',
            'username',
            'password',
        );
    },
    'slave2' => function () {
        return new ExtendedPdo(
            'mysql:host=slave2.db.localhost;dbname=database',
            'username',
            'password',
        );
    },
    'slave3' => function () {
        return new ExtendedPdo(
            'mysql:host=slave3.db.localhost;dbname=database',
            'username',
            'password',
        );
    },
);

// write connection
$write = array(
    'master' => function () {
        return new ExtendedPdo(
            'mysql:host=master.db.localhost;dbname=database',
            'username',
            'password',
        );
    },
);

// configure locator at construction time
$connections = new ConnectionLocator($default, $read, $write);
?>
```
