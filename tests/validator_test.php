<?php
declare(strict_types=1);

function runValidatorTests(): void
{
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

    $recipe = Recipe\RecipeValidator::parse($yaml);
    check($recipe['title'] === 'Zitronenreis', 'Gültiges Rezept wird gelesen.');
    check(count($recipe['blocks']) === 3, 'Überschrift, Text- und Tabellenblock bleiben erhalten.');
    check($recipe['blocks'][0]['type'] === 'heading', 'Kapitelüberschrift wird akzeptiert.');
    check($recipe['tags'] === ['Beilage', 'Vegetarisch'], 'Kategorietags werden gelesen.');
    check($recipe['aliases'] === ['Zitronen-Reis', 'Lemon Rice'], 'Alternativnamen werden gelesen.');
    check(Recipe\RecipeValidator::totalDuration($recipe) === 5, 'Blockdauern werden zur Gesamtdauer addiert.');
    check($recipe['blocks'][1]['waiting'] === 10, 'Blockwartezeiten werden gelesen und nicht zur Gesamtdauer addiert.');
    check(Recipe\RecipeValidator::totalWaiting($recipe) === 10, 'Blockwartezeiten werden separat summiert.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('duration: 5', 'duration: 0', $yaml)), 'Dauer null wird abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('duration: 5', 'duration: 1.5', $yaml)), 'Dezimalwerte als Dauer werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('waiting: 10', 'waiting: 0', $yaml)), 'Wartezeit null wird abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('waiting: 10', 'waiting: 1.5', $yaml)), 'Dezimalwerte als Wartezeit werden abgelehnt.');
    check(Recipe\RecipeValidator::formatDuration(120) === '120 min' && Recipe\RecipeValidator::formatDuration(121) === '2 h 1 min' && Recipe\RecipeValidator::formatDuration(180) === '3 h', 'Dauereinheiten werden kleingeschrieben und volle Stunden ohne null Minuten formatiert.');

    $youtubeYaml = str_replace("    - type: table\n", "    - type: youtube\n      url: https://youtu.be/dQw4w9WgXcQ\n      caption: Anleitung\n    - type: table\n", $yaml);
    $youtubeRecipe = Recipe\RecipeValidator::parse($youtubeYaml);
    check($youtubeRecipe['blocks'][2]['type'] === 'youtube', 'YouTube-Block wird akzeptiert.');
    check(Recipe\RecipeValidator::youtubeEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ') === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'YouTube-URL wird in eine datensparsame Embed-URL umgewandelt.');
    rejects(static fn () => Recipe\RecipeValidator::youtubeEmbedUrl('https://example.org/video'), 'Nicht-YouTube-URLs werden für YouTube-Blöcke abgelehnt.');

    $withSummary = str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: Frische Beilage mit Zitrone.\n", $yaml);
    check(Recipe\RecipeValidator::parse($withSummary)['summary'] === 'Frische Beilage mit Zitrone.', 'Optionale Kurzbeschreibung wird akzeptiert.');
    $withMainImage = str_replace("title: Zitronenreis\n", "title: Zitronenreis\nimage:\n  url: https://example.org/zitronenreis.jpg\n  alt: Zitronenreis mit Zitrone\n", $yaml);
    check(Recipe\RecipeValidator::parse($withMainImage)['image']['url'] === 'https://example.org/zitronenreis.jpg', 'Optionales Hauptbild wird akzeptiert.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nimage:\n  url: https://example.org/zitronenreis.jpg\n", $yaml)), 'Das Hauptbild benötigt einen Alt-Text.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nimage:\n  url: /private/image.jpg\n  alt: Bild\n", $yaml)), 'Das Hauptbild akzeptiert keine unbekannten lokalen Pfade.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: []\n", $yaml)), 'Kurzbeschreibung muss Text sein.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace("title: Zitronenreis\n", "title: Zitronenreis\nsummary: null\n", $yaml)), 'Explizit leere Kurzbeschreibung wird abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: Asiatisch', $yaml)), 'Tags müssen eine Liste sein.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('aliases: [Zitronen-Reis, Lemon Rice]', 'aliases: [Zitronen-Reis, zitronen-reis]', $yaml)), 'Doppelte Alternativnamen werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('aliases: [Zitronen-Reis, Lemon Rice]', 'aliases: []', $yaml)), 'Leere Alternativnamenliste wird abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: [Asiatisch, asiatisch]', $yaml)), 'Doppelte Tags werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('tags: [Beilage, Vegetarisch]', 'tags: [" Asiatisch", "Curry"]', $yaml)), 'Tags mit Rand-Leerzeichen werden abgelehnt.');
    check((bool) preg_match('/\A[a-z0-9-]+\z/', Recipe\RecipeValidator::slug($recipe['title'])), 'Titel ergibt einen sicheren Dateinamen.');

    rejects(static fn () => Recipe\RecipeValidator::parse("version: [\n"), 'Ungültige YAML wird abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('type: text', 'type: html', $yaml)), 'Unbekannte Blocktypen werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('duration: 5', 'duration: 0', $yaml)), 'Dauer null wird abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('duration: 5', 'duration: 1.5', $yaml)), 'Dezimalwerte als Dauer werden abgelehnt.');
    $unsafeMedia = <<<'YAML'
version: 1
title: Unsicheres Bild
blocks:
    - type: image
        url: javascript:alert(1)
        alt: Bild
YAML;
    rejects(static fn () => Recipe\RecipeValidator::parse($unsafeMedia), 'Nicht-HTTP-Medien-URLs werden abgelehnt.');

    $allBlockTypes = <<<'YAML'
version: 1
title: Alle Blocktypen
blocks:
    - { type: heading, text: Abschnitt }
    - type: text
      text: Beschreibung
    - type: table
      headers: [Zutat]
      rows: [[Reis]]
    - { type: image, url: https://example.org/image.jpg, alt: Bild, ratio: original }
    - { type: video, url: https://example.org/video.mp4 }
    - { type: audio, url: https://example.org/audio.mp3 }
    - { type: youtube, url: https://youtu.be/dQw4w9WgXcQ }
    - { type: link, url: https://example.org, label: Quelle }
YAML;
    $allBlockTypesRecipe = Recipe\RecipeValidator::parse($allBlockTypes);
    check(count($allBlockTypesRecipe['blocks']) === 8 && $allBlockTypesRecipe['blocks'][3]['ratio'] === 'original', 'Alle unterstützten Rezeptblocktypen und originale Bildverhältnisse werden akzeptiert.');
    $ratioRecipe = Recipe\RecipeValidator::parse(str_replace('ratio: original', 'ratio: 4 / 3', $allBlockTypes));
    check($ratioRecipe['blocks'][3]['ratio'] === '4 / 3', 'Numerische Bildverhältnisse werden akzeptiert.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('ratio: original', 'ratio: expression(alert(1))', $allBlockTypes)), 'Unsichere Bildverhältnisse werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('text: Beschreibung', "text: Beschreibung\n      extra: true", $allBlockTypes)), 'Unbekannte Blockfelder werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('headers: [Zutat]', 'headers: []', $allBlockTypes)), 'Tabellen ohne Spaltenüberschrift werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('rows: [[Reis]]', 'rows: [[Reis, 1]]', $allBlockTypes)), 'Tabellenzeilen mit falscher Spaltenzahl werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('label: Quelle', 'label: ""', $allBlockTypes)), 'Links ohne Label werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('url: https://example.org/video.mp4', 'url: ftp://example.org/video.mp4', $allBlockTypes)), 'Medien-URLs mit unsicherem Protokoll werden abgelehnt.');
    rejects(static fn () => Recipe\RecipeValidator::parse(str_replace('url: https://example.org/image.jpg', 'url: https://user:pass@example.org/image.jpg', $allBlockTypes)), 'Medien-URLs mit Zugangsdaten werden abgelehnt.');
    check(Recipe\RecipeValidator::youtubeEmbedUrl('https://youtube.com/shorts/dQw4w9WgXcQ') === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'YouTube-Shorts werden in datensparsame Embed-URLs umgewandelt.');
    check(Recipe\RecipeValidator::youtubeEmbedUrl('https://www.youtube.com/embed/dQw4w9WgXcQ') === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'YouTube-Embed-URLs werden akzeptiert.');
}
