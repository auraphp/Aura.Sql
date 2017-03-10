# 3.x Upgrade Notes

The vast majority of changes and breaks from the 2.x version are "under the
hood," so to speak. The main functionality methods (`query()`, `exec()`,
`perform()`, `fetch*()`, `yield*()`, etc.) are unchanged and work just the same
as in 2.x.

The remaining changes and breaks can be categorized as:

- the rebuilder and parsers;
- profiling and logging;
- instantiation; and
- miscellaneous.

## Rebuilder and Parsers

The 2.x _Rebuilder_ has been redone entirely, and now provides separate parsers
for the separate database drivers (mysql, pgsql, sqlite, and sqlsrv). This
originated from [#104](https://github.com/auraphp/Aura.Sql/issues/104), along
with [#107](https://github.com/auraphp/Aura.Sql/issues/107) and
[#111](https://github.com/auraphp/Aura.Sql/issues/111), all of which are now
resolved. It took a great deal of time and effort to complete, with several
approaches attempted.

### Array Placeholders

Given this code ...

```php
$stm = "SELECT * FROM test WHERE foo IN (:foo)";
$sth = $pdo->perform($stm, [
    'foo' => ['bar', 'baz', 'dib'];
]);
echo $sth->queryString;
```

... 2.x would quote and replace the array values directly into the query:

```
SELECT * FROM test WHERE foo IN ('bar', 'baz', 'dib')
```

Now, under 3.x, the placeholder is expanded to match the number of array keys,
so that there are multiple placeholders:

```
SELECT * FROM test WHERE foo IN (:foo_0, :foo_1, :foo_2)
```

The array values passed to the query will also be bound individually. (If you
profile the query, you will see `:foo_0` (et al.) in the bindings.)

### Sequential Placeholders

Given this code ...

```php
$stm = 'SELECT * FROM test WHERE foo = ? AND bar = ?';
$sth = $pdo->perform($stm, [
    'foo_value',
    'bar_value',
]);
echo $sth->queryString;
```

... the 2.x rebuilder would leave `?` placeholders alone:

```
SELECT * FROM test WHERE foo = ? AND bar = ?
```

Now, under 3.x, sequential placeholders are converted to named placeholders;
the name corresponds to the sequential position:

```
SELECT * FROM test WHERE foo = :__1 AND bar = :__2
```

The sequential values passed to the query will be bound to the named
replacements.

This helps to correct errors associated with binding sequential and named
placeholders together, and in identifiying bound values in longer query strings.

### Repetition of Named Placeholders

With 3.x, you can use the same placeholder multiple times. Given this code ...

```
$stm = 'SELECT * FROM test WHERE foo = :val OR bar = :val';
$sth = $pdo->perform($stm, [
    'val' => 'whatever',
]);
echo $sth->queryString;
```

... the 3.x parser modifies repeated placeholders by suffixing them each time
they reappear, and binds the needed values automatically:

```
SELECT * FROM test WHERE foo = :val OR bar = :val__1
```

### Custom Parsers

You can inject your own parsers as well via `ExtendedPdo::setParser()`. See
the `src/Parser/` directory for examples of the existing parsers.

## Profiling and Logging

The 2.x version used a custom profiler system, retaining profiles as array
constructs and returning them as such. You needed to inject it yourself.

```
use Aura\Sql\Profiler;

$pdo->setProfiler(new Profiler());

// ...
// query(), fetch(), beginTransaction()/commit()/rollback() etc.
// ...

// retrieve the profile information as a series of arrays
$profiles = $pdo->getProfiler()->getProfiles();
```

Under 3.x, the profiler interface itself remains custom, but it is now backed
with the [PSR-3 logger interface](http://www.php-fig.org/psr/psr-3/). This means
you can use any [PSR-3 implementation](https://packagist.org/providers/psr/log-implementation)
to capture profiler information. This means that the profiler emits strings,
rather than arrays, for the logger to capture.

A profiler is now automatically set on the ExtendedPdo instance, and uses an
in-memory logger by default for debugging purposes.


```
// no need to set a profiler, but you do need to activate it:
$pdo->getProfiler()->setActive();

// ...
// query(), fetch(), beginTransaction()/commit()/rollback() etc.
// ...

// retrieve the profiler logs from the default MemoryLogger
$messages = $pdo->getProfiler()->getLogger()->getMessages();
```

You can set the log message format and log level through the profiler, to
capture just the information you want.

You can set your own profiler and backing logger with the `setProfiler()`
method.

```php
use Aura\Sql\Profiler\Profiler;

$myLogger = new Psr3LoggerImplementation();
$pdo->setProfiler(new Profiler($myLogger));
```

Finally, under 2.x, ExtendedPdo would profile every function call. Unless you
examined very carefully, a call to `prepare()` followed by `perform()` looked
like 2 executions of the same query. To make it easier to examine logs, the 3.x
version does not profile every function call (e.g., `prepare()` and `prepareWithValues()`
are no longer logged).

## Instantiation

Under 2.x you would do this:

```php
$pdo = new ExtendedPdo(
    'pgsql:host=localhost;dbname=test',
    'username',
    'password',
    array(), // driver options as key-value pairs
    array()  // attributes as key-value pairs
);
```

It appears the last argument ended up being extraneous. The 3.x ExtendedPdo
changes the last argument to this:

```php
$pdo = new ExtendedPdo(
    'pgsql:host=localhost;dbname=test',
    'username',
    'password',
    [], // driver options as key-value pairs
    []  // queries to execute after connection
);
```

This lets you execute queries at connection time; e.g., to make connection
configuration changes that cannot be made as driver options/attributes. For
example:

```php
$pdo = new ExtendedPdo(
    'pgsql:host=localhost;dbname=test',
    'username',
    'password',
    [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
    ],
    [
        "SET NAMES 'utf8'",
    ]
)
```

## Miscellaneous

- PHP 5.6 is now the minimum required PHP version, though using the latest PHP
version is recommended.

- 3.x (as with previous versions) starts PDO in `ERRMODE_EXCEPTION` when no
error mode is explicitly specified.. However, the `sqlsrv` driver balks at this,
so it is started in `ERRMODE_WARNING` instead.

- The `ExtendedPdo::yield*()` methods now use the `yield` keyword instead of
returning `Iterator` instances.

- 1.x had a `quoteName()` method to quote identifier names. This was removed in
2.x, when that (and related functionality) were split off to SqlQuery. The
functionality is added back in 3.x as `quoteName()` and `quoteSingleName()` as a
convenience, though it is less robust than in SqlQuery.

- Some drivers cause PDO to make additional methods available (e.g., `sqlite` and
`pgsql`). The 3.x `ExtendedPdo` now proxies all unknown method calls to the
underlying PDO instance to make those methods available, if they exist.

- When dumping an ExtendedPdo object, the username and password are omitted. This
should help keep unexpected output of stack traces from revealing credentials.

