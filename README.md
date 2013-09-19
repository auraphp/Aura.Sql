# Aura.Sql

This library provides an extension to the PHP-native [PDO](http://php.net/PDO)
along with a profiler and service locator. Becuase _Aura\Sql\Pdo_ is an
extension of the native _PDO_, code already using the native _PDO_ or
typehinted to the native _PDO_ can use _Aura\Sql\Pdo_ without any changes.

> N.b.: This package is compatible with PHP 5.3; most Aura libraries require
> PHP 5.4.

Added functionality in _Aura.Sql_ over the native _PDO_ includes:

- **Lazy connection.** _Aura\Sql\Pdo_ connects to the database only on
  method calls that require a connection. This means you can create an
  instance and not incur the cost of a connection if you never make a query.

- **Bind values.** You may provide values for binding to the next query using
  `bindValues()`. Mulitple calls to `bindValues()` will merge, not reset, the
  values. The values will be reset after calling `query()`, `exec()`,
  `prepare()`, or any of the `fetch*()` methods.  In addition, binding values
  that do not have any corresponding placeholders will not cause an error.

- **Array quoting.** The `quote()` method will accept an array as input, and
  return a string of comma-separated quoted values. In addition, named
  placeholders in prepared statements that are bound to array values will
  be replaced with comma-separated quoted values. This means you can bind
  an array of values to a placeholder used with an `IN (...)` condition.

- **Quoting into placeholders.** The `quoteInto()` method will take a string
  with question-mark placeholders, and replace those placeholder with quoted
  values.

- **Quoting identifier names.** The `quoteName()` and `quoteNamesIn()` methods
  will quote identifer names (e.g., table names, index names, and so on).

- **Fetch methods.** _Aura\Sql\Pdo_ provides several `fetch*()` methods for
  commonly-used fetch styles. For example, you can call `fetchAll()` directly
  on the instance instead of having to prepare a statement, bind values,
  execute, and then fetch from the prepared statement. All of the `fetch*()`
  methods take an array of values to bind to to the query statement.

- **Exceptions by default.** _Aura\Sql\Pdo_ starts in the `ERRMODE_EXCEPTION`
  mode for error reporting instead of the `ERRMODE_SILENT` mode.

- **Profiler.** An optional query profiler is provided, along with an
  interface for other implementations.

- **Service locator.** A optional locator is available for picking different
  PDO connection services (default, read, and write).


## Preliminaries

### Installation and Autoloading

This library is installable via Composer and is registered on Packagist at
<https://packagist.org/packages/aura/autoload>. Installing via Composer will
set up autoloading automatically.

Alternatively, download or clone this repository, then require or include its
_autoload.php_ file.

### Dependencies and PHP Version

As with all Aura libraries, this library has no external dependencies. It
requires PHP version 5.3 or later (as opposed to most other Aura libraries,
which require PHP 5.4 or later).

### Tests

[![Build Status](https://travis-ci.org/auraphp/Aura.Autoload.png?branch=develop-2)](https://travis-ci.org/auraphp/Aura.Autoload)

This library has 100% code coverage. To run the library tests, first install
[PHPUnit][], then go to the library _tests_ directory and issue `phpunit` at
the command line.

[PHPUnit]: http://phpunit.de/manual/

### PSR Compliance

This library attempts to comply to [PSR-1][], [PSR-2][], and [PSR-4][]. If you
notice compliance oversights, please send a patch via pull request.

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
$pdo = new Aura\Sql\Pdo(
    'mysql:host=localhost;dbname=test',
    'username',
    'password',
    array(), // driver options as key-value pairs
    array()  // attributes as key-value pairs
);
?>
```


### Lazy Connection

Whereas the native _PDO_ connects on instantiation, _Aura\Sql\Pdo_ does not
connect immediately. Instead, it connects only when you call a method that
actually needs the connection to the database; e.g., on `query()`.

If you want to force a connection, call the `connect()` method.

```php
<?php
// does not connect to the database
$pdo = new Aura\Sql\Pdo(
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

Instead of having to bind values to a prepared `PDOStatement`, you can call
`bindValues()` directly on the _Aura\Sql\Pdo_ instance, and those values will
be bound to named placeholders in the next query.

```php
<?php
// the native PDO way
$pdo = new PDO(...);
$sth = $pdo->prepare('SELECT * FROM test WHERE foo = :foo AND bar = :bar');
$sth->bindValue('foo', 'foo_value');
$sth->bindValue('bar', 'bar_value');
$sth->execute();

// the Aura\Sql\Pdo way
$pdo = new Aura\Sql\Pdo(...);
$pdo->bindValues(array('foo' => 'foo_value', 'bar' => 'bar_value'));
$sth = $pdo->query('SELECT * FROM test WHERE foo = :foo AND bar = :bar');
?>
```


### Array Quoting

The native `PDO::quote()` method will not quote arrays. This makes it
difficult to bind an array to something like an `IN (...)` condition in SQL.
However, _Aura\Sql\Pdo_ recognizes arrays and converts them into
comma-separated quoted strings.

```php
<?php
// the array to be quoted
$array = array('foo', 'bar', 'baz');

// the native PDO way:
// "Warning:  PDO::quote() expects parameter 1 to be string, array given"
$pdo = new PDO(...);
$cond = 'IN (' . $pdo->quote($array) . ')';

// the Aura\Sql\Pdo way:
// "IN ('foo', 'bar', 'baz')"
$pdo = new Aura\Sql\Pdo(...);
$cond = 'IN (' . $pdo->quote($array) . ')'; 
?>
```

Whereas the native `PDO::prepare()` does not deal with bound array values,
_Aura\Sql\Pdo_ modifies the query string to replace the named placeholder with
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
$pdo = new Aura\Sql\Pdo(...);
$sth = $pdo->prepare($stm);
$sth->bindValue('foo', $array);

// the Aura\Sql\Pdo way quotes the array and replaces the array placeholder
// directly in the query string
$pdo = new Aura\Sql\Pdo(...);
$pdo->bindValues(array(
    'foo' => array('foo', 'bar', 'baz'),
    'bar' => 'qux',
));
$sth = $pdo->prepare($stm);
echo $sth->queryString;
// the query string has been modified by Pdo to become
// "SELECT * FROM test WHERE foo IN ('foo', 'bar', 'baz') AND bar = :bar"
?>
```

Finally, note that array quoting works only on the _Aura\Sql\Pdo_ instance,
not on returned _PDOStatement_ instances.


### Fetch Methods

_Aura\Sql\Pdo_ comes with `fetch*()` methods to help reduce boilerplate code.
Instead of issuing `prepare()`, a series of `bindValue()` calls, `execute()`,
and then `fetch*()` on a _PDOStatement_, you can bind values and fetch results
in one call.

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

// the Aura\Sql\Pdo way to do the same kind of "fetch all"
$pdo = new Aura\Sql\Pdo(...);
$result = $pdo->fetchAll($stm, $bind);

// fetchAssoc() returns an associative array of all rows where the key is the
// first column, and the row arrays are keyed on the column names
$result = $pdo->fetchAssoc($stm, $bind);

// fetchCol() returns a sequential array of all values in the first column
$result = $pdo->fetchCol($stm, $bind);

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


### Profiler

When debugging, it is often useful to see what queries have been executed,
where they were issued from in the codebase, and how long they took to
complete. _Aura\Sql\Pdo_ comes with an optional profiler that you can use to
discover that information.

```php
<?php
$pdo = new Aura\Sql\Pdo(...);
$pdo->setProfiler(new Aura\Sql\Profiler);

// ...
// query(), fetch(), beginTransaction()/commit()/rollback() etc.
// ...

// now retrieve the profile information:
$profiles = $pdo->getProfiler()->getProfiles();
?>
```

Each profile entry will have these keys:

- `function`: The method that was called on _Aura\Sql\Pdo_ that created the
  profile entry.

- `duration`: How long the query took to complete, in seconds.

- `statement`: The query string that was issued, if any. (Methods like
  `connect()` and `rollBack()` do not send query strings.)

- `bind_values`: Any values that were bound to the query.

- `trace`: An exception stack trace indicating where the query was issued from
  in the codebase.

Setting the _Profiler_ into the _Aura\Sql\Pdo_ instance is optional. Once it
it set, you can activate and deactivate it as you wish using the
`Profiler::setActive()` method. When not active, query profiles will not be
retained.

```php
<?php
$pdo = new Aura\Sql\Pdo(...);
$pdo->setProfiler(new Profiler);

// deactivate, issue a query, and reactivate;
// the query will not show up in the profiles
$pdo->getProfiler()->setActive(false);
$pdo->fetchAll('SELECT * FROM foo');
$pdo->getProfiler()->setActive(true);
?>
```

### Quoting Identifiers

(tbd)

### Factory

(tbd)

### Service Locator

(tbd)
