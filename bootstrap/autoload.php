<?php

// Define minimum supported PHP version
define('AKAUNTING_PHP', '8.1.0');

// Check PHP version
if (version_compare(PHP_VERSION, AKAUNTING_PHP, '<')) {
    $message = 'Error: Ask your hosting provider to use PHP ' . AKAUNTING_PHP . ' or higher for HTTP, CLI, and php command.' . PHP_EOL . PHP_EOL . 'Current PHP version: ' . PHP_VERSION . PHP_EOL;

    if (defined('STDOUT')) {
        fwrite(STDOUT, $message);
    } else {
        echo($message);
    }

    die(1);
}

// Load composer for core
require __DIR__ . '/../vendor/autoload.php';

// Optional: when DUMP_DEPRECATIONS=1 is set, convert deprecation notices to exceptions
// so PHPUnit will show file/line traces for vendor deprecations. This is temporary.
if (getenv('DUMP_DEPRECATIONS') === '1') {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        // fallback to PHP internal handler for other errors
        return false;
    });
}
