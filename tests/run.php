<?php
declare(strict_types=1);

use Recipe\RecipeRepository;
use Recipe\RecipeValidator;
use Recipe\ImageRepository;

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

function rejects(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (InvalidArgumentException) {
        check(true, $message);
        return;
    }
    check(false, $message);
}

$yaml = <<<'YAML'
version: 1
title: Zitronenreis
tags: [Beilage, Vegetarisch]
blocks:
    - type: heading
        text: Zubereitung
    - type: text
        text: Mit Zitrone abschmecken.
    - type: table
        headers: [Zutat, Menge]
        rows:
            - [Reis, "150 g"]
YAML;

$recipe = RecipeValidator::parse($yaml);
check($recipe['title'] === 'Zitronenreis', 'Gültiges Rezept wird gelesen.');
check(count($recipe['blocks']) === 3, 'Überschrift, Text- und Tabellenblock bleiben erhalten.');
check($recipe['blocks'][0]['type'] === 'heading', 'Kapitelüberschrift wird akzeptiert.');
check($recipe['tags'] === ['Beilage', 'Vegetarisch'], 'Kategorietags werden gelesen.');
$withSummary = str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: Frische Beilage mit Zitrone.\n", $yaml);
check(RecipeValidator::parse($withSummary)['summary'] === 'Frische Beilage mit Zitrone.', 'Optionale Kurzbeschreibung wird akzeptiert.');
rejects(static fn () => RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: []\n", $yaml)), 'Kurzbeschreibung muss Text sein.');
rejects(static fn () => RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: null\n", $yaml)), 'Explizit leere Kurzbeschreibung wird abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: Asiatisch', $yaml)), 'Tags müssen eine Liste sein.');
rejects(static fn () => RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: [Asiatisch, asiatisch]', $yaml)), 'Doppelte Tags werden abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: [" Asiatisch", "Curry"]', $yaml)), 'Tags mit Rand-Leerzeichen werden abgelehnt.');
check((bool) preg_match('/\A[a-z0-9-]+\z/', RecipeValidator::slug($recipe['title'])), 'Titel ergibt einen sicheren Dateinamen.');

rejects(static fn () => RecipeValidator::parse("version: [\n"), 'Ungültige YAML wird abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('type: text', 'type: html', $yaml)), 'Unbekannte Blocktypen werden abgelehnt.');
$unsafeMedia = <<<'YAML'
version: 1
title: Unsicheres Bild
blocks:
    - type: image
        url: javascript:alert(1)
        alt: Bild
YAML;
rejects(static fn () => RecipeValidator::parse($unsafeMedia), 'Nicht-HTTP-Medien-URLs werden abgelehnt.');

$directory = sys_get_temp_dir() . '/recipe-tests-' . bin2hex(random_bytes(6));
try {
    $repository = new RecipeRepository($directory);
    $imageRepository = new ImageRepository($directory);
    $temporaryImage = tempnam(sys_get_temp_dir(), 'recipe-image-test-');
    file_put_contents($temporaryImage, base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=', true));
    $imageUrl = $imageRepository->save($temporaryImage);
    unlink($temporaryImage);
    check((bool) preg_match('~\A/uploads/images/[a-f0-9]{32}\.gif\z~', $imageUrl), 'Bild erhält eine öffentliche URL mit zufälligem Dateinamen.');
    check(count($imageRepository->all()) === 1, 'Hochgeladenes Bild erscheint in der Bildliste.');
    $imageYaml = "version: 1\ntitle: Bildrezept\nblocks:\n  - type: image\n    url: {$imageUrl}\n    alt: Testbild\n";
    check(RecipeValidator::parse($imageYaml)['blocks'][0]['url'] === $imageUrl, 'Öffentlicher lokaler Bildpfad ist im Rezept-YAML gültig.');
    rejects(static fn () => RecipeValidator::parse("version: 1\ntitle: Unsicher\nblocks:\n  - type: image\n    url: /uploads/images/../../index.php\n    alt: Bild\n"), 'Andere lokale Dateipfade werden nicht als Rezeptbild akzeptiert.');

    $slug = $repository->save($yaml, $recipe);
    check($slug === 'zitronenreis', 'Neues Rezept erhält einen stabilen Slug.');
    check($repository->find($slug)['recipe']['title'] === 'Zitronenreis', 'Gespeichertes Rezept kann geladen werden.');
    check($repository->find($slug)['modifiedAt'] > 0, 'Änderungszeit wird für die Datumssortierung bereitgestellt.');

    $replacement = str_replace('Mit Zitrone abschmecken.', 'Mit frischer Zitrone abschmecken.', $yaml);
    $repository->save($replacement, RecipeValidator::parse($replacement));
    check($repository->find($slug)['recipe']['blocks'][0]['text'] === 'Mit frischer Zitrone abschmecken.', 'Gleicher Titel ersetzt das bestehende Rezept.');
    $invalidReplacement = $replacement . "unknown: true\n";
    rejects(static fn () => RecipeValidator::parse($invalidReplacement), 'Unbekannte Rezeptfelder werden abgelehnt.');
    check($repository->find($slug)['recipe']['blocks'][0]['text'] === 'Mit frischer Zitrone abschmecken.', 'Ungültige Änderungen lassen das gespeicherte Rezept unverändert.');
    check(count($repository->all()) === 1, 'Die Sammlung listet gespeicherte Rezepte.');
    check($repository->delete($slug), 'Rezept lässt sich löschen.');
    check(!$repository->delete($slug), 'Gelöschtes Rezept kann nicht erneut gelöscht werden.');
} finally {
    foreach (glob($directory . '/*') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}

fwrite(STDOUT, "{$checks} Prüfungen erfolgreich.\n");