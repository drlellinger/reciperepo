<?php
declare(strict_types=1);

use Recipe\RecipeRepository;
use Recipe\RecipeValidator;
use Recipe\ImageRepository;
use Recipe\UploadedFiles;

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

check(uploadErrorMessage(UPLOAD_ERR_INI_SIZE) === 'Das Bild ist größer als das vom Server erlaubte Limit.', 'PHP-Uploadfehler für zu große Dateien wird korrekt erfasst.');
check(uploadErrorMessage(UPLOAD_ERR_NO_FILE) === 'Es wurde keine Datei ausgewählt.', 'Fehlender Upload wird korrekt erkannt.');
check(\Recipe\Toast::message(['message' => 'Rezept gelöscht.']) === 'Rezept gelöscht.', 'Flash-Nachrichten werden als Toast-Meldung übernommen.');
check(\Recipe\Toast::message(['message' => '   ']) === null, 'Leere Flash-Nachrichten werden als Toast ignoriert.');
$footerName = Recipe\FooterMarkdownRenderer::render('reciperepo · [Kontakt](mailto:post@example.org)');
check($footerName === 'reciperepo · <a href="mailto:post@example.org">Kontakt</a>', 'Markdown-Links im Footer-Namen werden sicher gerendert.');
$customPrivacyLink = Recipe\FooterMarkdownRenderer::renderLinkSetting('[Datenschutz](https://example.org/privacy)', 'Datenschutzerklärung');
check($customPrivacyLink === '<a href="https://example.org/privacy">Datenschutz</a>', 'Footer-Links akzeptieren Markdown mit individuellem Linktext.');
$legacyPrivacyLink = Recipe\FooterMarkdownRenderer::renderLinkSetting('https://example.org/privacy', 'Datenschutzerklärung');
check($legacyPrivacyLink === '<a href="https://example.org/privacy">Datenschutzerklärung</a>', 'Bisherige reine Footer-URLs behalten ihr Standardlabel.');
$unsafeFooterLink = Recipe\FooterMarkdownRenderer::render('[Unsicher](javascript:alert(1))');
check(!str_contains($unsafeFooterLink, '<a ') && str_contains($unsafeFooterLink, 'javascript:'), 'Unsichere Markdown-Linkprotokolle werden nicht als Links ausgegeben.');
$normalizedUploads = UploadedFiles::normalize([
    'name' => ['one.yaml', 'two.yaml'],
    'type' => ['application/yaml', 'application/yaml'],
    'tmp_name' => ['/tmp/one', '/tmp/two'],
    'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
    'size' => [10, 20],
]);
check(count($normalizedUploads) === 2 && $normalizedUploads[1]['name'] === 'two.yaml', 'Mehrfach-Uploads werden in einzelne Dateien normalisiert.');
check(UploadedFiles::normalize(null) === [], 'Fehlende Uploads ergeben eine leere Dateiliste.');
$remainingRemoteImages = 6;
$pdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
    ['type' => 'heading', 'text' => 'Zubereitung'],
    ['type' => 'text', 'duration' => 5, 'text' => 'Mit Zitrone abschmecken.'],
    ['type' => 'video', 'url' => 'https://example.org/video.mp4', 'caption' => 'Video'],
], dirname(__DIR__) . '/public', $remainingRemoteImages);
check(str_contains($pdfBlocks, '<h2>Zubereitung</h2>') && str_contains($pdfBlocks, 'Mit Zitrone abschmecken.') && str_contains($pdfBlocks, 'duration-icon') && str_contains($pdfBlocks, '5 Min.'), 'PDF-Rezeptblöcke enthalten Überschrift, Text, Uhrsymbol und Dauer.');
check(!str_contains($pdfBlocks, '<button') && !str_contains($pdfBlocks, '<video') && !str_contains($pdfBlocks, '<audio'), 'PDF-Rezeptblöcke enthalten keine Buttons oder Medien-Steuerelemente.');
$privateImageBlocks = Recipe\RecipePdfRenderer::renderBlocks([
    ['type' => 'image', 'url' => 'http://127.0.0.1/internal.png', 'alt' => 'Privates Bild'],
], dirname(__DIR__) . '/public', $remainingRemoteImages);
check(!str_contains($privateImageBlocks, '<img'), 'Private IP-Adressen werden beim PDF-Bildabruf blockiert.');

$yaml = <<<'YAML'
version: 1
title: Zitronenreis
aliases: [Zitronen-Reis, Lemon Rice]
tags: [Beilage, Vegetarisch]
blocks:
    - type: heading
      text: Zubereitung
    - type: text
      duration: 5
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
check($recipe['aliases'] === ['Zitronen-Reis', 'Lemon Rice'], 'Alternativnamen werden gelesen.');
check(RecipeValidator::totalDuration($recipe) === 5, 'Blockdauern werden zur Gesamtdauer addiert.');
check(RecipeValidator::formatDuration(120) === '120 min' && RecipeValidator::formatDuration(121) === '2 h 1 min' && RecipeValidator::formatDuration(180) === '3 h', 'Dauereinheiten werden kleingeschrieben und volle Stunden ohne null Minuten formatiert.');
$youtubeYaml = str_replace("    - type: table\n", "    - type: youtube\n      url: https://youtu.be/dQw4w9WgXcQ\n      caption: Anleitung\n    - type: table\n", $yaml);
$youtubeRecipe = RecipeValidator::parse($youtubeYaml);
check($youtubeRecipe['blocks'][2]['type'] === 'youtube', 'YouTube-Block wird akzeptiert.');
check(RecipeValidator::youtubeEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ') === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'YouTube-URL wird in eine datensparsame Embed-URL umgewandelt.');
rejects(static fn () => RecipeValidator::youtubeEmbedUrl('https://example.org/video'), 'Nicht-YouTube-URLs werden für YouTube-Blöcke abgelehnt.');
$youtubePdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
    ['type' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'caption' => 'Anleitung'],
], dirname(__DIR__) . '/public', $remainingRemoteImages);
check(!str_contains($youtubePdfBlocks, '<iframe') && !str_contains($youtubePdfBlocks, '<video') && str_contains($youtubePdfBlocks, 'href="https://youtu.be/dQw4w9WgXcQ"') && str_contains($youtubePdfBlocks, '>Anleitung</a>'), 'YouTube-Videos werden im PDF nicht gerendert, sondern mit der Beschreibung verlinkt.');
$withSummary = str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: Frische Beilage mit Zitrone.\n", $yaml);
check(RecipeValidator::parse($withSummary)['summary'] === 'Frische Beilage mit Zitrone.', 'Optionale Kurzbeschreibung wird akzeptiert.');
$withMainImage = str_replace("title: Zitronenreis\n", "title: Zitronenreis\nimage:\n  url: https://example.org/zitronenreis.jpg\n  alt: Zitronenreis mit Zitrone\n", $yaml);
check(RecipeValidator::parse($withMainImage)['image']['url'] === 'https://example.org/zitronenreis.jpg', 'Optionales Hauptbild wird akzeptiert.');
rejects(static fn () => RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nimage:\n  url: https://example.org/zitronenreis.jpg\n", $yaml)), 'Das Hauptbild benötigt einen Alt-Text.');
rejects(static fn () => RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nimage:\n  url: /private/image.jpg\n  alt: Bild\n", $yaml)), 'Das Hauptbild akzeptiert keine unbekannten lokalen Pfade.');
rejects(static fn () => RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: []\n", $yaml)), 'Kurzbeschreibung muss Text sein.');
rejects(static fn () => RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: null\n", $yaml)), 'Explizit leere Kurzbeschreibung wird abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: Asiatisch', $yaml)), 'Tags müssen eine Liste sein.');
rejects(static fn () => RecipeValidator::parse(str_replace('aliases: [Zitronen-Reis, Lemon Rice]', 'aliases: [Zitronen-Reis, zitronen-reis]', $yaml)), 'Doppelte Alternativnamen werden abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('aliases: [Zitronen-Reis, Lemon Rice]', 'aliases: []', $yaml)), 'Leere Alternativnamenliste wird abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: [Asiatisch, asiatisch]', $yaml)), 'Doppelte Tags werden abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: [" Asiatisch", "Curry"]', $yaml)), 'Tags mit Rand-Leerzeichen werden abgelehnt.');
check((bool) preg_match('/\A[a-z0-9-]+\z/', RecipeValidator::slug($recipe['title'])), 'Titel ergibt einen sicheren Dateinamen.');

rejects(static fn () => RecipeValidator::parse("version: [\n"), 'Ungültige YAML wird abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('type: text', 'type: html', $yaml)), 'Unbekannte Blocktypen werden abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('duration: 5', 'duration: 0', $yaml)), 'Dauer null wird abgelehnt.');
rejects(static fn () => RecipeValidator::parse(str_replace('duration: 5', 'duration: 1.5', $yaml)), 'Dezimalwerte als Dauer werden abgelehnt.');
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
    $localPdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        ['type' => 'image', 'url' => '/' . basename($temporaryImage), 'alt' => 'Testbild'],
    ], sys_get_temp_dir(), $remainingRemoteImages);
    check(str_contains($localPdfBlocks, 'src="file://' . realpath($temporaryImage) . '"'), 'Lokale Rezeptbilder erhalten eine explizite file-URL für Dompdf.');
    $temporarySvg = tempnam(sys_get_temp_dir(), 'recipe-logo-test-');
    file_put_contents($temporarySvg, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0h10v10z" fill="#28664e"/></svg>');
    $svgPdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        ['type' => 'image', 'url' => '/' . basename($temporarySvg), 'alt' => 'Logo'],
    ], sys_get_temp_dir(), $remainingRemoteImages);
    check(str_contains($svgPdfBlocks, 'data:image/svg+xml;base64,'), 'SVG-Logos werden als eingebettete Grafik übernommen.');
    $svgSource = preg_match('/src="data:image\/svg\+xml;base64,([^"]+)"/', $svgPdfBlocks, $svgMatch) === 1
        ? base64_decode($svgMatch[1], true)
        : false;
    check(is_string($svgSource) && !str_contains($svgSource, '<script') && str_contains($svgSource, '<path'), 'SVG-Logos behalten Grafikpfade, aber keine Skripte.');
    unlink($temporarySvg);
    $jpegImage = imagecreatetruecolor(2, 3);
    ob_start();
    imagejpeg($jpegImage);
    $jpegBytes = ob_get_clean();
    imagedestroy($jpegImage);
    $exifPayload = "Exif\0\0II*\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
    $jpegBytes = substr($jpegBytes, 0, 2) . "\xFF\xE1" . pack('n', strlen($exifPayload) + 2) . $exifPayload . substr($jpegBytes, 2);
    $temporaryJpeg = tempnam(sys_get_temp_dir(), 'recipe-orientation-test-');
    file_put_contents($temporaryJpeg, $jpegBytes);
    $orientedPdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        ['type' => 'image', 'url' => '/' . basename($temporaryJpeg), 'alt' => 'Hochkantbild'],
    ], sys_get_temp_dir(), $remainingRemoteImages);
    $orientedImageBytes = preg_match('/src="data:image\/jpeg;base64,([^"]+)"/', $orientedPdfBlocks, $jpegMatch) === 1
        ? base64_decode($jpegMatch[1], true)
        : false;
    $orientedDimensions = is_string($orientedImageBytes) ? getimagesizefromstring($orientedImageBytes) : false;
    check(is_array($orientedDimensions) && $orientedDimensions[0] === 3 && $orientedDimensions[1] === 2, 'JPEGs mit EXIF-Hochkantorientierung werden vor dem PDF gedreht.');
    unlink($temporaryJpeg);
    unlink($temporaryImage);
    check((bool) preg_match('~\A/uploads/images/[a-f0-9]{32}\.gif\z~', $imageUrl), 'Bild erhält eine öffentliche URL mit zufälligem Dateinamen.');
    check(count($imageRepository->all()) === 1, 'Hochgeladenes Bild erscheint in der Bildliste.');
    $jpegImage = imagecreatetruecolor(2, 3);
    ob_start();
    imagejpeg($jpegImage);
    $jpegBytes = ob_get_clean();
    imagedestroy($jpegImage);
    $exifPayload = "Exif\0\0II*\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
    $jpegWithExif = substr($jpegBytes, 0, 2) . "\xFF\xE1" . pack('n', strlen($exifPayload) + 2) . $exifPayload . substr($jpegBytes, 2);
    $temporaryJpeg = tempnam(sys_get_temp_dir(), 'recipe-upload-exif-');
    file_put_contents($temporaryJpeg, $jpegWithExif);
    $sanitizedJpegUrl = $imageRepository->save($temporaryJpeg);
    $sanitizedJpegPath = $directory . '/' . basename($sanitizedJpegUrl);
    $sanitizedJpegDimensions = getimagesize($sanitizedJpegPath);
    check(is_array($sanitizedJpegDimensions) && $sanitizedJpegDimensions[0] === 3 && $sanitizedJpegDimensions[1] === 2, 'JPEG-Ausrichtung wird vor dem Entfernen der EXIF-Daten korrigiert.');
    $sanitizedExif = @exif_read_data($sanitizedJpegPath, null, true);
    check(!is_array($sanitizedExif) || !isset($sanitizedExif['EXIF']), 'Hochgeladene JPEGs enthalten keine EXIF-Metadaten mehr.');
    unlink($temporaryJpeg);
    $imageYaml = "version: 1\ntitle: Bildrezept\nblocks:\n  - type: image\n    url: {$imageUrl}\n    alt: Testbild\n";
    check(RecipeValidator::parse($imageYaml)['blocks'][0]['url'] === $imageUrl, 'Öffentlicher lokaler Bildpfad ist im Rezept-YAML gültig.');
    rejects(static fn () => RecipeValidator::parse("version: 1\ntitle: Unsicher\nblocks:\n  - type: image\n    url: /uploads/images/../../index.php\n    alt: Bild\n"), 'Andere lokale Dateipfade werden nicht als Rezeptbild akzeptiert.');

    $slug = $repository->save($yaml, $recipe);
    check($slug === 'zitronenreis', 'Neues Rezept erhält einen stabilen Slug.');
    check($repository->find($slug)['recipe']['title'] === 'Zitronenreis', 'Gespeichertes Rezept kann geladen werden.');
    check($repository->find($slug)['modifiedAt'] > 0, 'Änderungszeit wird für die Datumssortierung bereitgestellt.');

    $replacement = str_replace('Mit Zitrone abschmecken.', 'Mit frischer Zitrone abschmecken.', $yaml);
    $repository->save($replacement, RecipeValidator::parse($replacement));
    check($repository->find($slug)['recipe']['blocks'][1]['text'] === 'Mit frischer Zitrone abschmecken.', 'Gleicher Titel ersetzt das bestehende Rezept.');
    $invalidReplacement = $replacement . "unknown: true\n";
    rejects(static fn () => RecipeValidator::parse($invalidReplacement), 'Unbekannte Rezeptfelder werden abgelehnt.');
    check($repository->find($slug)['recipe']['blocks'][1]['text'] === 'Mit frischer Zitrone abschmecken.', 'Ungültige Änderungen lassen das gespeicherte Rezept unverändert.');
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