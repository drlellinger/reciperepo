<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$checks = 0;

function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE => 'Das Bild ist größer als das vom Server erlaubte Limit.',
        UPLOAD_ERR_FORM_SIZE => 'Die Datei überschreitet das vom Formular erlaubte Limit.',
        UPLOAD_ERR_PARTIAL => 'Der Upload wurde nur teilweise übertragen.',
        UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei ausgewählt.',
        UPLOAD_ERR_CANT_WRITE => 'Das temporäre Verzeichnis konnte nicht beschrieben werden.',
        UPLOAD_ERR_EXTENSION => 'Ein PHP-Upload-Filter hat den Upload blockiert.',
        default => 'Das Bild konnte nicht hochgeladen werden.',
    };
}

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
