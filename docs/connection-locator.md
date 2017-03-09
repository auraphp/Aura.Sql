# Connection Locator

Frequently, high-traffic PHP applications use multiple database servers,
generally one for writes, and one or more for reads. The _ConnectionLocator_
allows you to define multiple _ExtendedPdo_ objects for lazy-loaded read and
write connections. It will create the connections only when they are when
called. The creation logic is wrapped in a callable.

## Runtime Configuration

First, create the _ConnectionLocator_:

```php
use Aura\Sql\ExtendedPdo;
use Aura\Sql\ConnectionLocator;

$connections = new ConnectionLocator();
```

Now add a default connection; this will be used when a read or write
connection is not defined. (This is also useful for setting up connection
location in advance of actually having multiple database servers.)

```php
$connections->setDefault(function () {
    return new ExtendedPdo(
        'mysql:host=default.db.localhost;dbname=database',
        'username',
        'password'
    );
});
```

Next, add as many named read and write connections as you like:

```php
// the write (master) server
$connections->setWrite('master', function () {
    return new ExtendedPdo(
        'mysql:host=master.db.localhost;dbname=database',
        'username',
        'password'
    );
});

// read (slave) #1
$connections->setRead('slave1', function () {
    return new ExtendedPdo(
        'mysql:host=slave1.db.localhost;dbname=database',
        'username',
        'password'
    );
});

// read (slave) #2
$connections->setRead('slave2', function () {
    return new ExtendedPdo(
        'mysql:host=slave2.db.localhost;dbname=database',
        'username',
        'password'
    );
});

// read (slave) #3
$connections->setRead('slave3', function () {
    return new ExtendedPdo(
        'mysql:host=slave3.db.localhost;dbname=database',
        'username',
        'password'
    );
});
```

Finally, retrieve a connection from the locator when you need it. This will
create the connection (if needed) and then return it.

- `getDefault()` will return the default connection.

- `getRead()` will return a named read connection; if no name is specified, it
  will return a random read connection. If no read connections are defined, it
  will return the default connection.

- `getWrite()` will return a named write connection; if no name is specified,
  it will return a random write connection. If no write connections are
  defined, it will return the default connection.

```php
$read = $connections->getRead();
$results = $read->fetchAll('SELECT * FROM table_name LIMIT 10');
```

## Construction-Time Configuration

The _ConnectionLocator_ can be configured with all its connections at
construction time; this is useful with dependency injection mechanisms.

```php
use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;

// default connection
$default = function () {
    return new ExtendedPdo(
        'mysql:host=default.db.localhost;dbname=database',
        'username',
        'password'
    );
};

// read connections
$read = array(
    'slave1' => function () {
        return new ExtendedPdo(
            'mysql:host=slave1.db.localhost;dbname=database',
            'username',
            'password'
        );
    },
    'slave2' => function () {
        return new ExtendedPdo(
            'mysql:host=slave2.db.localhost;dbname=database',
            'username',
            'password'
        );
    },
    'slave3' => function () {
        return new ExtendedPdo(
            'mysql:host=slave3.db.localhost;dbname=database',
            'username',
            'password'
        );
    },
);

// write connection
$write = array(
    'master' => function () {
        return new ExtendedPdo(
            'mysql:host=master.db.localhost;dbname=database',
            'username',
            'password'
        );
    },
);

// configure locator at construction time
$connections = new ConnectionLocator($default, $read, $write);
```
