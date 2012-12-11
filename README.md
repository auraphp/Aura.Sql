Aura SQL
========

The Aura SQL package provides connections to connect to and query against SQL
data sources such as MySQL, PostgreSQL, and Sqlite. The connections are mostly
wrappers around [PDO](http://php.net/PDO) connections.

This package is compliant with [PSR-0][], [PSR-1][], and [PSR-2][]. If you
notice compliance oversights, please send a patch via pull request.

[PSR-0]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md

Getting Started
===============

Instantiation
-------------

The easiest way to get started is to use the `scripts/instance.php` script to
get a `ConnectionFactory` and create your connection through it:

```php
<?php
$connection_factory = include '/path/to/Aura.Sql/scripts/instance.php';
$connection = $connection_factory->newInstance(
    
    // adapter name
    'mysql',
    
    // DSN elements for PDO; this can also be
    // an array of key-value pairs
    'host=localhost;dbname=database_name',
    
    // username for the connection
    'username',
    
    // password for the connection
    'password'
);
```

Alternatively, you can add `'/path/to/Aura.Sql/src'` to your autoloader and
build an connection factory manually:
    
```php
<?php
use Aura\Sql\ConnectionFactory;
$connection_factory = new ConnectionFactory;
$connection = $connection_factory->newInstance(...);
```
    
Aura SQL comes with four connection adapters: `'mysql'` for MySQL, `'pgsql'`
for PostgreSQL, `'sqlite'` for SQLite3, and `'sqlsrv'` for Microsoft SQL
Server.

Connecting
----------

The connection will lazy-connect to the database the first time you issue a
query of any sort. This means you can create the connection object, and if you
never issue a query, it will never connect to the database.

You can connect manually by issuing `connect()`:

```php
<?php
$connection->connect();
```


Fetching Results
----------------

Once you have a connection, you can begin to fetch results from the database.

```php
<?php
// returns all rows
$result = $connection->fetchAll('SELECT * FROM foo');
```

You can fetch results using these methods:

- `fetchAll()` returns a sequential array of all rows. The rows themselves are
  associative arrays where the keys are the column names.

- `fetchAssoc()` returns an associative array of all rows where the key is the
  first column.

- `fetchCol()` returns a sequential array of all values in the first column.

- `fetchOne()` returns the first row as an associative array where the keys
  are the column names.

- `fetchPairs()` returns an associative array where each key is the first
  column and each value is the second column.

- `fetchValue()` returns the value of the first row in the first column.


Preventing SQL Injection
------------------------

Usually you will need to incorporate user-provided data into the query. This
means you should quote all values interpolated into the query text as a
security measure to [prevent SQL injection](http://bobby-tables.com/).

Although Aura SQL provides quoting methods, you should instead use value
binding into prepared statements. To do so, put named placeholders in the
query text, then pass an array of values to bind to the placeholders:

```php
<?php
// the text of the query
$text = 'SELECT * FROM foo WHERE id = :id';

// values to bind to query placeholders
$bind = [
    'id' => 1,
];

// returns one row; the data has been parameterized
// into a prepared statement for you
$result = $connection->fetchOne($text, $bind);
```

Aura SQL recognizes array values and quotes them as comma-separated lists:

```php
<?php
// the text of the query
$text = 'SELECT * FROM foo WHERE id = :id AND bar IN(:bar_list)';

// values to bind to query placeholders
$bind = [
    'id' => 1,
    'bar_list' => ['a', 'b', 'c'],
];

// returns all rows; the query ends up being
// "SELECT * FROM foo WHERE id = 1 AND bar IN('a', 'b', 'c')"
$result = $connection->fetchOne($text, $bind);
```


Modifying Rows
--------------

Aura SQL comes with three convenience methods for modifying data: `insert()`,
`update()`, and `delete()`. You can also retrieve the last inserted ID using
`lastInsertId()`.

First, to insert a row:

```php
<?php
// the table to insert into
$table = 'foo';

// the columns and values to insert
$cols = [
    'bar' => 'value for column bar',
];

// perform the insert; result is number of rows affected
$result = $connection->insert($table, $cols);

// now get the last inserted ID
$id = $connection->lastInsertId();
```

(N.b.: Because of the way PostgreSQL creates auto-incremented columns, the
`pgsql` adapter needs to know the table and column name to get the last
inserted ID; for example, `$id = $connection->lastInsertId($table, 'id');`.)

Next, to update rows:

```php
<?php
// the table to update
$table = 'foo';

// the new column values to set
$cols = [
    'bar' => 'a new value for column bar',
];

// a where condition to specify which rows to update
$cond = 'id = :id';

// additional data to bind to the query
$bind = ['id' => 1];

// perform the update; result is number of rows affected
$result = $connection->update($table, $cols, $cond, $bind);
```

> (N.b.: Both `$cols` and `$bind` are bound into the update query, but `$cols`
> takes precedence. Be sure that the keys in `$cols` and `$bind` do not
> conflict.)

Finally, to delete rows:

```php
<?php
// the table to delete from
$table = 'foo';

// a where condition to specify which rows to delete
$cond = 'id = :id';

// data to bind to the query
$bind = ['id' => 1];

// perform the deletion; result is number of rows affected
$result = $connection->delete($table, $cond, $bind);
```


Retrieving Table Information
----------------------------

To get a list of tables in the database, issue `fetchTableList()`:

```php
<?php
// get the list of tables
$list = $connection->fetchTableList();

// show them
foreach ($list as $table) {
    echo $table . PHP_EOL;
}
```

To get information about the columns in a table, issue `fetchTableCols()`:

```php
<?php
// the table to get cols for
$table = 'foo';

// get the cols
$cols = $connection->fetchTableCols($table);

// show them
foreach ($cols as $name => $col) {
    echo "Column $name is of type "
       . $col->type
       . " with a size of "
       . $col->size
       . PHP_EOL;
}
```

Each column description is a `Column` object with the following properties:

- `name`: (string) The column name

- `type`: (string) The column data type.  Data types are as reported by the database.

- `size`: (int) The column size.

- `scale`: (int) The number of decimal places for the column, if any.

- `notnull`: (bool) Is the column marked as `NOT NULL`?

- `default`: (mixed) The default value for the column. Note that sometimes this will be `null` if the underlying database is going to set a timestamp automatically.

- `autoinc`: (bool) Is the column auto-incremented?

- `primary`: (bool) Is the column part of the primary key?

Transactions
------------

Aura SQL connections always start in autocommit mode (the same as PDO). However,
you can turn off autocommit mode and start a transaction with
`beginTransaction()`, then either `commit()` or `rollBack()` the transaction.
Commits and rollbacks cause the connection to go back into autocommit mode.

```php
<?php
// turn off autocommit and start a transaction
$connection->beginTransaction();

try {
    // ... perform some queries ...
    // now commit to the database:
    $connection->commit();
} catch (Exception $e) {
    // there was an error, roll back the queries
    $connection->rollBack();
}

// at this point we are back in autocommit mode
```

    
Manual Queries
--------------

You can, of course, build and issue your own queries by hand. Use the
`query()` method to do so:

```php
<?php
$text = "SELECT * FROM foo WHERE id = :id";
$bind = ['id' => 1];
$stmt = $connection->query($text, $bind)
```

The returned `$stmt` is a [PDOStatement](http://php.net/PDOStatement) that you
may manipulate as you wish.

Profiling
---------

You can use profiling to see how well your queries are performing.

```php
<?php
// turn on the profiler
$connection->getProfiler()->setActive(true);

// issue a query
$result = $connection->fetchAll('SELECT * FROM foo');

// now get the profiler information
foreach ($connection->getProfiler()->getProfiles() as $i => $profile) {
    echo 'Query #' . ($i + 1)
       . ' took ' . $profile->time . ' seconds.'
       . PHP_EOL;
}
```
    
Each profile object has these properties:

- `text`: (string) The text of the query.

- `time`: (float) The time, in seconds, for the query to finish.

- `data`: (array) Any data bound to the query.

- `trace`: (array) A [debug_backtrace](http://php.net/debug_backtrace) so
  you can tell where the query came from.


Query Objects
=============

Aura SQL provides four types of query objects so you can write your SQL
queries in an object-oriented way.

Select
------

To get a new `Select` object, invoke the `newSelect()` method on an connection.
You can then modify the `Select` object and pass it to the `query()` or
`fetch*()` method.

```php
<?php
// create a new Select object
$select = $connection->newSelect();

// SELECT * FROM foo WHERE bar > :bar ORDER BY baz
$select->cols(['*'])
       ->from('foo')
       ->where('bar > :bar')
       ->orderBy('baz');

$bind = ['bar' => '88'];

$list = $connection->fetchAll($select, $bind);
```

The `Select` object has these methods and more; please read the source code
for more information.

- `distinct()`: Set to `true` for a `SELECT DISTINCT`.

- `cols()`: Select these columns.

- `from()`: Select from these tables.

- `join()`: Join these tables on specified conditions.

- `where()`: `WHERE` these conditions are met (using `AND`).

- `orWhere()`: `WHERE` these conditions are met (using `OR`).

- `groupBy()`: `GROUP BY` these columns.

- `having()`: `HAVING` these conditions met (using `AND`).

- `orHaving()`: `HAVING` these conditions met (using `OR`).

- `orderBy()`: `ORDER BY` these columns.

- `limit()`: `LIMIT` to this many rows.

- `offset()`: `OFFSET` by this many rows.

- `union()`: `UNION` with a followup `SELECT`.

- `unionAll()`: `UNION ALL` with a followup `SELECT`.

Insert
------

To get a new `Insert` object, invoke the `newInsert()` method on an connection.
You can then modify the `Insert` object and pass it to the `query()` method.

```php
<?php
// create a new Insert object
$insert = $connection->newInsert();

// INSERT INTO foo (bar, baz, date) VALUES (:bar, :baz, NOW());
$insert->into('foo')
       ->cols(['bar', 'baz'])
       ->set('date', 'NOW()');

$bind = [
    'bar' => null,
    'baz' => 'zim',
];

$stmt = $connection->query($insert, $bind);
```

Update
------

To get a new `Update` object, invoke the `newUpdate()` method on an connection.
You can then modify the `Update` object and pass it to the `query()` method.

```php
<?php
// create a new Update object
$update = $connection->newUpdate();

// UPDATE foo SET bar = :bar, baz = :baz, date = NOW() WHERE zim = :zim OR gir = :gir
$update->table('foo')
       ->cols(['bar', 'baz'])
       ->set('date', 'NOW()')
       ->where('zim = :zim')
       ->orWhere('gir = :gir');

$bind = [
    'bar' => 'barbar',
    'baz' => 99,
    'zim' => 'dib',
    'gir' => 'doom',
];

$stmt = $connection->query($update, $bind);
```

Delete
------

To get a new `Delete` object, invoke the `newDelete()` method on an connection.
You can then modify the `Delete` object and pass it to the `query()` method.

```php
<?php
// create a new Delete object
$delete = $connection->newDelete();

// DELETE FROM WHERE zim = :zim OR gir = :gir
$delete->from('foo')
       ->where('zim = :zim')
       ->orWhere('gir = :gir');

$bind = [
    'zim' => 'dib',
    'gir' => 'doom',
];

$stmt = $connection->query($delete, $bind);
```
