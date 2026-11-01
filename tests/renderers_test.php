<?php
declare(strict_types=1);

function runRendererTests(): void
{
    check(Recipe\UploadErrorMessage::forCode(UPLOAD_ERR_INI_SIZE) === 'Die Datei ist größer als das vom Server erlaubte Limit. Bitte upload_max_filesize und post_max_size prüfen.', 'PHP-Uploadfehler für zu große Dateien wird korrekt erfasst.');
    check(Recipe\UploadErrorMessage::forCode(UPLOAD_ERR_NO_FILE) === 'Es wurde keine Datei ausgewählt.', 'Fehlender Upload wird korrekt erkannt.');
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
    $formattedText = Recipe\InlineMarkdownRenderer::render('**Fett**, *kursiv*, ***beides*** und ~~alt~~ <script>alert(1)</script>');
    check($formattedText === '<strong>Fett</strong>, <em>kursiv</em>, <strong><em>beides</em></strong> und <del>alt</del> &lt;script&gt;alert(1)&lt;/script&gt;', 'Markdown-Textformatierung wird sicher gerendert.');

    $normalizedUploads = Recipe\UploadedFiles::normalize([
        'name' => ['one.yaml', 'two.yaml'],
        'type' => ['application/yaml', 'application/yaml'],
        'tmp_name' => ['/tmp/one', '/tmp/two'],
        'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'size' => [10, 20],
    ]);
    check(count($normalizedUploads) === 2 && $normalizedUploads[1]['name'] === 'two.yaml', 'Mehrfach-Uploads werden in einzelne Dateien normalisiert.');
    check(Recipe\UploadedFiles::normalize(null) === [], 'Fehlende Uploads ergeben eine leere Dateiliste.');

    $remainingRemoteImages = 6;
    $pdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        ['type' => 'heading', 'text' => 'Zubereitung'],
        ['type' => 'text', 'duration' => 5, 'waiting' => 10, 'text' => 'Mit Zitrone abschmecken.'],
        ['type' => 'video', 'url' => 'https://example.org/video.mp4', 'caption' => 'Video'],
    ], dirname(__DIR__) . '/public', $remainingRemoteImages);
    check(str_contains($pdfBlocks, '<h2>Zubereitung</h2>') && str_contains($pdfBlocks, 'Mit Zitrone abschmecken.') && str_contains($pdfBlocks, 'duration-icon') && str_contains($pdfBlocks, '5 Min.') && str_contains($pdfBlocks, 'recipe-waiting') && str_contains($pdfBlocks, 'Wartezeit: 10 Min.'), 'PDF-Rezeptblöcke rendern Dauer und Wartezeit getrennt.');
    check(!str_contains($pdfBlocks, '<button') && !str_contains($pdfBlocks, '<video') && !str_contains($pdfBlocks, '<audio'), 'PDF-Rezeptblöcke enthalten keine Buttons oder Medien-Steuerelemente.');

    $privateImageBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        ['type' => 'image', 'url' => 'http://127.0.0.1/internal.png', 'alt' => 'Privates Bild'],
    ], dirname(__DIR__) . '/public', $remainingRemoteImages);
    check(!str_contains($privateImageBlocks, '<img'), 'Private IP-Adressen werden beim PDF-Bildabruf blockiert.');

    $youtubePdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        ['type' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'caption' => 'Anleitung'],
    ], dirname(__DIR__) . '/public', $remainingRemoteImages);
    check(!str_contains($youtubePdfBlocks, '<iframe') && !str_contains($youtubePdfBlocks, '<video') && str_contains($youtubePdfBlocks, 'href="https://youtu.be/dQw4w9WgXcQ"') && str_contains($youtubePdfBlocks, '>Anleitung</a>'), 'YouTube-Videos werden im PDF nicht gerendert, sondern mit der Beschreibung verlinkt.');

    $additionalPdfBlocks = Recipe\RecipePdfRenderer::renderBlocks([
        [
            'type' => 'table',
            'caption' => 'Zutaten & Hinweise',
            'headers' => ['Zutat'],
            'rows' => [['Reis & Salz']],
        ],
        ['type' => 'audio', 'url' => 'https://example.org/audio.mp3', 'caption' => 'Audio <Test>'],
        ['type' => 'link', 'url' => 'https://example.org/source', 'label' => 'Quelle <Test>'],
    ], dirname(__DIR__) . '/public', $remainingRemoteImages);
    check(str_contains($additionalPdfBlocks, '<caption>Zutaten &amp; Hinweise</caption>') && str_contains($additionalPdfBlocks, '<td>Reis &amp; Salz</td>'), 'PDF-Tabellen enthalten Caption und sicher escapte Zellen.');
    check(str_contains($additionalPdfBlocks, 'Audio &lt;Test&gt;') && !str_contains($additionalPdfBlocks, '<audio'), 'PDF-Audio wird als sicherer Hinweis ohne Steuerelement gerendert.');
    check(str_contains($additionalPdfBlocks, 'href="https://example.org/source"') && str_contains($additionalPdfBlocks, '>Quelle &lt;Test&gt;</a>'), 'PDF-Links werden mit sicher escaptem Label gerendert.');
}
