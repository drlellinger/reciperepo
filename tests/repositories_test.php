<?php
declare(strict_types=1);

function runRepositoryTests(): void
{
    $directory = sys_get_temp_dir() . '/recipe-tests-' . bin2hex(random_bytes(6));
    try {
        $repository = new Recipe\RecipeRepository($directory);
        $imageRepository = new Recipe\ImageRepository($directory);
        $temporaryImage = tempnam(sys_get_temp_dir(), 'recipe-image-test-');
        file_put_contents($temporaryImage, base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=', true));
        $imageUrl = $imageRepository->save($temporaryImage);
        $remainingRemoteImages = 6;
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
        $embeddedLogoBlocks = Recipe\RecipePdfRenderer::renderBlocks([
            ['type' => 'image', 'url' => '/assets/logo.svg', 'alt' => 'Logo'],
        ], dirname(__DIR__) . '/public', $remainingRemoteImages);
        $embeddedLogoSource = preg_match('/src="data:image\/svg\+xml;base64,([^"]+)"/', $embeddedLogoBlocks, $embeddedLogoMatch) === 1
            ? base64_decode($embeddedLogoMatch[1], true)
            : false;
        check(is_string($embeddedLogoSource) && str_contains($embeddedLogoSource, '<image') && str_contains($embeddedLogoSource, 'data:image/png;base64,'), 'SVG-Logos mit eingebettetem Rasterbild bleiben im PDF erhalten.');
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

        $yaml = <<<'YAML'
version: 1
title: Zitronenreis
aliases: [Zitronen-Reis, Lemon Rice]
tags: [Beilage, Vegetarisch]
blocks:
    - type: heading
      text: Zubereitung
    - { type: text, duration: 5, waiting: 10, text: 'Mit Zitrone abschmecken.' }
    - type: table
      headers: [Zutat, Menge]
      rows:
        - [Reis, "150 g"]
YAML;
        $imageYaml = "version: 1\ntitle: Bildrezept\nblocks:\n  - type: image\n    url: {$imageUrl}\n    alt: Testbild\n";
        check(Recipe\RecipeValidator::parse($imageYaml)['blocks'][0]['url'] === $imageUrl, 'Öffentlicher lokaler Bildpfad ist im Rezept-YAML gültig.');
        rejects(static fn () => Recipe\RecipeValidator::parse("version: 1\ntitle: Unsicher\nblocks:\n  - type: image\n    url: /uploads/images/../../index.php\n    alt: Bild\n"), 'Andere lokale Dateipfade werden nicht als Rezeptbild akzeptiert.');

        $slug = $repository->save($yaml, Recipe\RecipeValidator::parse($yaml));
        check($slug === 'zitronenreis', 'Neues Rezept erhält einen stabilen Slug.');
        check($repository->find($slug)['recipe']['title'] === 'Zitronenreis', 'Gespeichertes Rezept kann geladen werden.');
        check($repository->find($slug)['modifiedAt'] > 0, 'Änderungszeit wird für die Datumssortierung bereitgestellt.');

        $replacement = str_replace('Mit Zitrone abschmecken.', 'Mit frischer Zitrone abschmecken.', $yaml);
        $repository->save($replacement, Recipe\RecipeValidator::parse($replacement));
        check($repository->find($slug)['recipe']['blocks'][1]['text'] === 'Mit frischer Zitrone abschmecken.', 'Gleicher Titel ersetzt das bestehende Rezept.');
        $invalidReplacement = $replacement . "unknown: true\n";
        rejects(static fn () => Recipe\RecipeValidator::parse($invalidReplacement), 'Unbekannte Rezeptfelder werden abgelehnt.');
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
}
