<?php
declare(strict_types=1);

namespace Recipe;

final class FooterMarkdownRenderer
{
    public static function render(string $markdown): string
    {
        preg_match_all('/\[([^\]]+)\]\(([^)\s]+)\)/u', $markdown, $matches, PREG_OFFSET_CAPTURE);
        if ($matches[0] === []) {
            return self::escape($markdown);
        }

        $html = '';
        $offset = 0;
        foreach ($matches[0] as $index => [$match, $matchOffset]) {
            $html .= self::escape(substr($markdown, $offset, $matchOffset - $offset));
            $label = $matches[1][$index][0];
            $url = $matches[2][$index][0];
            if (self::isSafeUrl($url)) {
                $html .= '<a href="' . self::escape($url) . '">' . self::escape($label) . '</a>';
            } else {
                $html .= self::escape($match);
            }
            $offset = $matchOffset + strlen($match);
        }

        return $html . self::escape(substr($markdown, $offset));
    }

    public static function renderLinkSetting(string $value, string $defaultLabel): string
    {
        $value = trim($value);
        if (self::isSafeUrl($value)) {
            return '<a href="' . self::escape($value) . '">' . self::escape($defaultLabel) . '</a>';
        }

        return self::render($value);
    }

    private static function isSafeUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\\\\]/', $url) === 1 || str_starts_with($url, '//')) {
            return false;
        }

        if (preg_match('/\A([a-z][a-z0-9+.-]*):/i', $url, $matches) === 1) {
            return in_array(strtolower($matches[1]), ['http', 'https', 'mailto', 'tel'], true);
        }

        return true;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}