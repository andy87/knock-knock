<?php

declare(strict_types=1);

function check(bool $condition, string $message = 'assertion failed'): void
{
    $condition || throw new RuntimeException($message);
}

function same(mixed $expected, mixed $actual, string $label = ''): void
{
    check(
        $expected === $actual,
        ($label !== '' ? "$label: " : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true),
    );
}

/** @param class-string<Throwable> $class */
function throws(string $class, callable $fn): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        check($e instanceof $class, "expected $class, got " . $e::class . ': ' . $e->getMessage());

        return $e;
    }
    throw new RuntimeException("expected $class, nothing thrown");
}
