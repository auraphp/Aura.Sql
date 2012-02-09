Aura Sql
========

The Aura Sql package provides adapters to connect to and query against SQL data sources such as MySQL, PostgreSQL, and Sqlite.  The adapters are mostly wrappers around [PDO](http://php.net/PDO) connections.


Getting Started
===============

Instantiation
-------------

The easiest way to get started is to use the `scripts/instance.php` script to get an `AdapterFactory` and create your adapter through it:

    <?php
    $adapter_factory = include '/path/to/Aura.Sql/scripts/instance.php';
    $sql = $adapter_factory->newInstance(
        
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

Alternatively, you can add `'/path/to/Aura.Sql/src` to your autoloader and build an adapter factory manually:
    
    <?php
    use Aura\Sql\AdapterFactory;
    $adapter_factory = new AdapterFactory;
    $sql = $adapter_factory->newInstance(...);
    
Aura Sql comes with three adapters: `'mysql'` for MySQL, `'pgsql'` for PostgreSQL, and `'sqlite'` for SQLite3.

Connecting
----------

The adapter will lazy-connect to the database the first time you issue a query of any sort.  This means you can create the adapter object, and if you never issue a query, it will never connect to the database.

You can connect manually by issuing `connect()`:

    <?php
    $sql->connect();


Fetching Results
----------------

Once you have an adapter connection, you can begin to fetch results from the database.

    <?php
    // returns all rows
    $result = $sql->fetchAll('SELECT * FROM foo');

You can fetch results using these methods:

- `fetchAll()` returns a sequential array of all rows. The rows themselves are associative arrays where the keys are the column names.

- `fetchAssoc()` returns an associative array of all rows where the key is the first column.

- `fetchCol()` returns a sequential array of all values in the first column.

- `fetchOne()` returns the first row as an associative array where the keys are the column names.

- `fetchPairs()` returns an associative array where each key is the first column and each value is the second column.

- `fetchValue()` returns the value of the first row in the first column.


Preventing SQL Injection
------------------------

Usually you will need to incorporate user-provided data into the query. This means you should quote all values interpolated into the query text as a security measure to [prevent SQL injection](http://bobby-tables.com/).

Although Aura Sql provides quoting methods, you should instead use value binding into prepared statements.  To do so, put named placeholders in the query text, then pass an array of values to bind to the placeholders:

    <?php
    // the text of the query
    $text = 'SELECT * FROM foo WHERE id = :id';
    
    // values to bind to query placeholders
    $data = [
        'id' => 1,
    ];
    
    // returns one row; the data has been parameterized
    // into a prepared statement for you
    $result = $sql->fetchOne($text, $data);

Aura Sql recognizes array values and quotes them as comma-separated lists:

    <?php
    // the text of the query
    $text = 'SELECT * FROM foo WHERE id = :id AND bar IN(:bar_list)';
    
    // values to bind to query placeholders
    $data = [
        'id' => 1,
        'bar_list' => ['a', 'b', 'c'],
    ];
    
    // returns all rows; the query ends up being
    // "SELECT * FROM foo WHERE id = 1 AND bar IN('a', 'b', 'c')"
    $result = $sql->fetchOne($text, $data);


Modifying Rows
--------------

Aura Sql comes with three convenience methods for modifying data: `insert()`, `update()`, and `delete()`. You can also retrieve the last inserted ID using `lastInsertId()`.

First, to insert a row:

    <?php
    // the table to insert into
    $table = 'foo';
    
    // the columns and values to insert
    $cols = [
        'bar' => 'value for column bar',
    ];
    
    // perform the insert; result is number of rows affected
    $result = $sql->insert($table, $cols);
    
    // now get the last inserted ID
    $id = $sql->lastInsertId();

(N.b.: Because of the way PostgreSQL creates auto-incremented columns, the `pgsql` adapter needs to know the table and column name to get the last inserted ID; for example, `$id = $sql->lastInsertId($table, 'id');`.)

Next, to update rows:

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
    $data = ['id' => 1];
    
    // perform the update; result is number of rows affected
    $result = $sql->update($table, $cols, $cond, $data);

(N.b.: Both `$cols` and `$data` are bound into the update query, but `$cols` takes precedence.  Be sure that the keys in `$cols` and `$data` do not conflict.)

Finally, to delete rows:

    <?php
    // the table to delete from
    $table = 'foo';
    
    // a where condition to specify which rows to delete
    $cond = 'id = :id';
    
    // data to bind to the query
    $data = ['id' => 1];
    
    // perform the deletion; result is number of rows affected
    $result = $sql->delete($table, $cond, $data);

Retrieving Table Information
----------------------------

To get a list of tables in the database, issue `fetchTableList()`:

    <?php
    // get the list of tables
    $list = $sql->fetchTableList();
    
    // show them
    foreach ($list as $table) {
        echo $table . PHP_EOL;
    }

To get information about the columns in a table, issue `fetchTableCols()`:

    <?php
    // the table to get cols for
    $table = 'foo';
    
    // get the cols
    $cols = $sql->fetchTableCols($table);
    
    // show them
    foreach ($cols as $name => $col) {
        echo "Column $name is of type "
           . $col->type
           . " with a size of "
           . $col->size
           . PHP_EOL;
    }

Each column description is a `Column` object with the following properties:

- `name`: (string) The column name

- `type`: (string) The column data type.  Data types are as reported by the database.

- `size`: (int) The column size.

- `scale`: (int) The number of decimal places for the column, if any.

- `notnull`: (bool) Is the column marked as `NOT NULL`?

- `default`: (mixed) The default value for the column. Note that sometimes this will be `null` if the underlying database is going to set a timestamp automatically.

- `autoinc`: (bool) Is the column auto-incremented?

- `primary`: (bool) Is the column part of the primary key?

Manual Queries
--------------

You can, of course, build and issue your own queries by hand.  Use the `query()` method to do so:

    <?php
    $text = "SELECT * FROM foo WHERE id = :id";
    $data = ['id' => 1];
    $stmt = $sql->query($text, $data)

The returned `$stmt` is a [PDOStatement](http://php.net/PDOStatement) that you may manipulate as you wish.

Profiling
---------

You can use profiling to see how well your queries are performing.

    <?php
    // turn on the profiler
    $sql->getProfiler()->setActive(true);

    // issue a query
    $result = $sql->fetchAll('SELECT * FROM foo');
    
    // now get the profiler information
    foreach ($sql->getProfiler()->getProfiles() as $i => $profile) {
        echo 'Query #' . $i + 1
           . ' took ' . $profile->time . ' seconds.'
           . PHP_EOL;
    }
    
Each profile object has these properties:

- `text`: (string) The text of the query.

- `time`: (float) The time, in seconds, for the query to finish.

- `data`: (array) Any data bound to the query.

- `trace`: (array) A [debug_backtrace](http://php.net/debug_backtrace) so
  you can tell where the query came from.
