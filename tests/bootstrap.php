<?php
// preload source files
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src.php';

// autoload test files
spl_autoload_register(function($class) {
    $file = dirname(__DIR__). DIRECTORY_SEPARATOR
          . 'tests' . DIRECTORY_SEPARATOR
          . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// set up globals for connection information
$base = __DIR__ . DIRECTORY_SEPARATOR . 'globals';
if (file_exists("$base.php")) {
    // user-defined globals.php
    require_once "$base.php";
    echo "Database connection values read from $base.php" . PHP_EOL;
} else {
    // default globals-dist.php
    require_once "$base-dist.php";
    echo "Database connection values read from $base-dist.php" . PHP_EOL;
}
