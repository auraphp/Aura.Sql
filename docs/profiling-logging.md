# Profiling and Logging

It is often useful to see what queries have been executed, where they were
issued from in the codebase, and how long they took to complete. As such,
_ExtendedPdo_ comes with a profiler that logs to any [PSR-3 implementation][].
The profiler defaults to a naive memory-based logger for debugging purposes.

[PSR-3 implementation]: (https://packagist.org/providers/psr/log-implementation)

## Using The Profiler

You can activate and deactivate the profiler using the `Profiler::setActive()`
method. (Messages are not logged when the profiler is not active.)

You can then examine the log messages using the underlying log system;
in the below example, we use the default `MemoryLogger` implementation.

```php
<?php
// activate the profiler
$pdo->getProfiler()->setActive(true);

// ...
// query(), fetch(), beginTransaction()/commit()/rollback() etc.
// ...

// now retrieve the array messages from the default memory logger:
$messages = $pdo->getProfiler()->getLogger()->getMessages();
print_r($messages);
```

## Other Logger Implementations

You can set your own profiler and PSR-3 logger implementation using the
`ExtendedPdo::setProfiler()` method.

```php
use Aura\Sql\Profiler\Profiler;

$myLogger = new Psr3LoggerImplementation();
$pdo->setProfiler(new Profiler($myLogger));
```

## Profiler Log Messages

Profiler log messages, by default, will match this format:

    {function} ({duration} seconds): {statement} {backtrace}

You can customize the message format using the `Profiler::setLogFormat()`
method, like so:

```php
$pdo->getProfiler()->setLogFormat("{duration}: {function} {statement}")
```

The context keys are:

- `{function}`: The method that was called on _ExtendedPdo_ that created the
  profile entry.

- `{start}`: The microtime when the profile began.

- `{finish}`: The microtime when the profile ended.

- `{duration}`: The profile duration, in seconds.

- `{statement}`: The query string that was issued, if any. (Methods like
  `connect()` and `rollBack()` do not send query strings.)

- `{values}`: The values bound to the statement, if any.

- `{backtrace}`: An exception stack trace indicating where the query was issued
  from in the codebase.

## Profiler Log Level

By default, all messages are logged at the `DEBUG` level. You can change the
logging level with the `Profiler::setLogLevel()` method:

```php
use Psr\Log\LogLevel;

$pdo->getProfiler()->setLogLevel(LogLevel::INFO);
```

Likewise, you can get the current log level with `Profiler::getLogLevel()`.
