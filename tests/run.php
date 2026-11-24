<?php

declare(strict_types=1);

/**
 * Zero-dependency test runner: `php tests/run.php`.
 * Each tests/*Test.php file returns an array of name => callable.
 */

require __DIR__ . '/../autoload.php';
require __DIR__ . '/Support/assert.php';

error_reporting(E_ALL);
set_error_handler(static function (int $no, string $str, string $file, int $line): never {
    throw new ErrorException($str, 0, $no, $file, $line);
});

$passed = $failed = 0;
foreach (glob(__DIR__ . '/*Test.php') as $file) {
    foreach ((require $file) as $name => $test) {
        try {
            $test();
            $passed++;
            echo "  ok   " . basename($file, '.php') . " :: $name\n";
        } catch (Throwable $e) {
            $failed++;
            echo "  FAIL " . basename($file, '.php') . " :: $name\n       " . $e::class . ': ' . $e->getMessage()
                . "\n       at " . basename($e->getFile()) . ':' . $e->getLine() . "\n";
        }
    }
}

echo "\n$passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
