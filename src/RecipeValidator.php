<?php
declare(strict_types=1);

namespace Recipe;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

final class RecipeValidator
{
    public static function parse(string $contents): array
    {
        if ($contents === '' || strlen($contents) > 1048576) {
            throw new InvalidArgumentException('Die YAML-Datei ist leer oder größer als 1 MB.');
        }

        try {
            $recipe = Yaml::parse($contents, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Die YAML-Datei kann nicht gelesen werden.', 0, $exception);
        }

        if (!is_array($recipe) || ($recipe['version'] ?? null) !== 1) {
            throw new InvalidArgumentException('Das Rezept muss version: 1 enthalten.');
        }
        self::validateKeys($recipe, ['version', 'title', 'summary', 'aliases', 'tags', 'image', 'blocks'], 'Rezept');

        if (!isset($recipe['title']) || !is_string($recipe['title']) || trim($recipe['title']) === '' || strlen($recipe['title']) > 160) {
            throw new InvalidArgumentException('Der Titel muss zwischen 1 und 160 Zeichen lang sein.');
        }

        if (!isset($recipe['blocks']) || !is_array($recipe['blocks']) || !array_is_list($recipe['blocks'])) {
            throw new InvalidArgumentException('blocks muss eine geordnete Liste sein.');
        }

        if (array_key_exists('summary', $recipe) && (!is_string($recipe['summary']) || trim($recipe['summary']) === '')) {
            throw new InvalidArgumentException('summary muss ein nicht leerer Text sein.');
        }

        if (array_key_exists('aliases', $recipe)) {
            self::validateAliases($recipe['aliases']);
        }

        if (array_key_exists('image', $recipe)) {
            self::validateRecipeImage($recipe['image']);
        }

        if (array_key_exists('tags', $recipe)) {
            if (!is_array($recipe['tags']) || !array_is_list($recipe['tags'])) {
                throw new InvalidArgumentException('tags muss eine Liste von Textwerten sein.');
            }
            foreach ($recipe['tags'] as $tag) {
                if (!is_string($tag) || trim($tag) === '' || trim($tag) !== $tag || strlen($tag) > 80) {
                    throw new InvalidArgumentException('Jeder Tag muss nicht leer und höchstens 80 UTF-8-Bytes lang sein.');
                }
            }
            if (count(array_unique(array_map('strtolower', $recipe['tags']))) !== count($recipe['tags'])) {
                throw new InvalidArgumentException('Tags dürfen nicht doppelt vorkommen.');
            }
        }

        foreach ($recipe['blocks'] as $index => $block) {
            self::validateBlock($block, $index + 1);
        }

        return $recipe;
    }

    public static function totalDuration(array $recipe): int
    {
        $duration = 0;
        foreach ($recipe['blocks'] ?? [] as $block) {
            $duration += $block['duration'] ?? 0;
        }

        return $duration;
    }

    public static function formatDuration(int $duration): string
    {
        if ($duration <= 120) {
            return $duration . ' min';
        }

        $hours = intdiv($duration, 60);
        $minutes = $duration % 60;

        return $minutes === 0 ? $hours . ' h' : $hours . ' h ' . $minutes . ' min';
    }

    private static function validateAliases(mixed $aliases): void
    {
        if (!is_array($aliases) || !array_is_list($aliases) || $aliases === []) {
            throw new InvalidArgumentException('aliases muss eine nicht leere Liste von Textwerten sein.');
        }

        foreach ($aliases as $alias) {
            if (!is_string($alias) || trim($alias) === '' || trim($alias) !== $alias || strlen($alias) > 160) {
                throw new InvalidArgumentException('Jeder Alternativname muss nicht leer und höchstens 160 UTF-8-Bytes lang sein.');
            }
        }
        if (count(array_unique(array_map('strtolower', $aliases))) !== count($aliases)) {
            throw new InvalidArgumentException('Alternativnamen dürfen nicht doppelt vorkommen.');
        }
    }

    public static function slug(string $title): string
    {
        $asciiTitle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($title));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $asciiTitle === false ? $title : $asciiTitle));
        $slug = trim($slug, '-');

        if ($slug === '') {
            throw new InvalidArgumentException('Aus dem Titel lässt sich kein Dateiname bilden.');
        }

        return $slug;
    }

    private static function validateBlock(mixed $block, int $number): void
    {
        if (!is_array($block) || !isset($block['type']) || !is_string($block['type'])) {
            throw new InvalidArgumentException("Inhaltsblock {$number} benötigt einen Typ.");
        }

        switch ($block['type']) {
            case 'heading':
                self::validateKeys($block, ['type', 'text', 'duration'], "Inhaltsblock {$number}");
                self::requireString($block, 'text', $number);
                break;
            case 'text':
                self::validateKeys($block, ['type', 'text', 'caption', 'duration'], "Inhaltsblock {$number}");
                self::requireString($block, 'text', $number);
                break;
            case 'table':
                self::validateKeys($block, ['type', 'headers', 'rows', 'caption', 'duration'], "Inhaltsblock {$number}");
                self::validateTable($block, $number);
                break;
            case 'image':
                self::validateKeys($block, ['type', 'url', 'alt', 'caption', 'duration'], "Inhaltsblock {$number}");
                self::validateMedia($block, $number);
                self::requireString($block, 'alt', $number);
                break;
            case 'video':
            case 'audio':
                self::validateKeys($block, ['type', 'url', 'caption', 'duration'], "Inhaltsblock {$number}");
                self::validateMedia($block, $number);
                break;
            case 'youtube':
                self::validateKeys($block, ['type', 'url', 'caption', 'duration'], "Inhaltsblock {$number}");
                self::youtubeEmbedUrl($block['url'] ?? null, $number);
                break;
            case 'link':
                self::validateKeys($block, ['type', 'url', 'label', 'caption', 'duration'], "Inhaltsblock {$number}");
                self::validateUrl($block['url'] ?? null, $number);
                self::requireString($block, 'label', $number);
                break;
            default:
                throw new InvalidArgumentException("Inhaltsblock {$number} hat einen unbekannten Typ.");
        }

        if (isset($block['caption']) && !is_string($block['caption'])) {
            throw new InvalidArgumentException("Die Beschriftung in Inhaltsblock {$number} muss Text sein.");
        }

        if (array_key_exists('duration', $block) && (!is_int($block['duration']) || $block['duration'] < 1)) {
            throw new InvalidArgumentException("Die Dauer in Inhaltsblock {$number} muss eine positive ganze Minutenzahl sein.");
        }
    }

    private static function validateTable(array $block, int $number): void
    {
        $headers = $block['headers'] ?? null;
        $rows = $block['rows'] ?? null;
        if (!is_array($headers) || $headers === [] || !array_is_list($headers) || !is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException("Tabelle {$number} benötigt headers und rows als Listen.");
        }

        foreach ($headers as $header) {
            if (!is_string($header) || trim($header) === '') {
                throw new InvalidArgumentException("Alle Spaltenüberschriften in Tabelle {$number} müssen Text enthalten.");
            }
        }

        foreach ($rows as $row) {
            if (!is_array($row) || !array_is_list($row) || count($row) !== count($headers)) {
                throw new InvalidArgumentException("Jede Zeile in Tabelle {$number} muss zu den Spalten passen.");
            }
            foreach ($row as $cell) {
                if (!is_string($cell) && !is_int($cell) && !is_float($cell)) {
                    throw new InvalidArgumentException("Tabellenzellen in Tabelle {$number} müssen Text oder Zahlen sein.");
                }
            }
        }
    }

    private static function validateMedia(array $block, int $number): void
    {
        self::validateUrl($block['url'] ?? null, $number, $block['type'] === 'image');
    }

    private static function validateRecipeImage(mixed $image): void
    {
        if (!is_array($image)) {
            throw new InvalidArgumentException('image muss url und alt enthalten.');
        }
        self::validateKeys($image, ['url', 'alt'], 'Rezeptbild');
        self::validateUrl($image['url'] ?? null, 0, true);
        if (!isset($image['alt']) || !is_string($image['alt']) || trim($image['alt']) === '') {
            throw new InvalidArgumentException('Das Rezeptbild benötigt einen nicht leeren Alt-Text.');
        }
    }

    public static function youtubeEmbedUrl(mixed $url, int $number = 0): string
    {
        if (!is_string($url)) {
            throw new InvalidArgumentException($number > 0
                ? "Inhaltsblock {$number} benötigt eine YouTube-URL."
                : 'Der YouTube-Block benötigt eine URL.');
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            $segments = $path === '' ? [] : explode('/', $path);
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $videoId = $query['v'] ?? null;
            } elseif (count($segments) === 2 && in_array($segments[0], ['embed', 'shorts'], true)) {
                $videoId = $segments[1];
            }
        } elseif ($host === 'youtu.be' && $path !== '' && !str_contains($path, '/')) {
            $videoId = $path;
        }

        if (!is_string($videoId) || !preg_match('/\A[A-Za-z0-9_-]{11}\z/', $videoId)) {
            throw new InvalidArgumentException($number > 0
                ? "Inhaltsblock {$number} benötigt eine gültige YouTube-URL."
                : 'Der YouTube-Block benötigt eine gültige YouTube-URL.');
        }

        return 'https://www.youtube-nocookie.com/embed/' . $videoId;
    }

    private static function validateUrl(mixed $url, int $number, bool $allowManagedImage = false): void
    {
        if (!is_string($url)) {
            throw new InvalidArgumentException("Inhaltsblock {$number} benötigt eine URL.");
        }

        if ($allowManagedImage && preg_match('/\A\/uploads\/images\/[a-f0-9]{32}\.(?:jpg|png|gif|webp)\z/', $url)) {
            return;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException("Inhaltsblock {$number} benötigt eine sichere HTTP(S)-URL.");
        }
    }

    private static function requireString(array $block, string $key, int $number): void
    {
        if (!isset($block[$key]) || !is_string($block[$key]) || trim($block[$key]) === '') {
            throw new InvalidArgumentException("Inhaltsblock {$number} benötigt den Textwert {$key}.");
        }
    }

    private static function validateKeys(array $value, array $allowed, string $context): void
    {
        if (array_diff(array_keys($value), $allowed) !== []) {
            throw new InvalidArgumentException("{$context} enthält ein unbekanntes Feld.");
        }
    }
}