<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$checks = 0;

function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function runTest(string $name, callable $test): void
{
    global $checks;
    $checksBefore = $checks;
    fwrite(STDOUT, "[START] {$name}\n");

    try {
        $test();
    } catch (Throwable $exception) {
        fwrite(STDERR, "[FAIL] {$name}: {$exception->getMessage()}\n");
        throw $exception;
    }

    $checksInTest = $checks - $checksBefore;
    fwrite(STDOUT, "[ OK ] {$name} ({$checksInTest} Prüfungen)\n");
}

function rejects(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (InvalidArgumentException) {
        check(true, $message);
        return;
    } catch (Throwable $exception) {
        check(false, $message . ' Unerwartete Exception: ' . $exception::class . '.');
    }
    check(false, $message);
}

function rejectsAny(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (Throwable) {
        check(true, $message);
        return;
    }
    check(false, $message);
}
