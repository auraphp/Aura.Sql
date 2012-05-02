<?php
spl_autoload_register(function($class) {
    $dir   = dirname(__DIR__);
    $file  = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $src = $dir . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR . $file;
    if (file_exists($src)) {
        require $src;
    }
    $tests = $dir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $file;
    if (file_exists($tests)) {
        require $tests;
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
