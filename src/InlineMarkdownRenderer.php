<?php
declare(strict_types=1);

namespace Recipe;

final class InlineMarkdownRenderer
{
    public static function render(string $markdown): string
    {
        $escaped = htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $patterns = [
            '/\*\*\*([^*]+)\*\*\*/u' => '<strong><em>$1</em></strong>',
            '/___([^_]+)___/u' => '<strong><em>$1</em></strong>',
            '/\*\*([^*]+)\*\*/u' => '<strong>$1</strong>',
            '/__([^_]+)__/u' => '<strong>$1</strong>',
            '/~~([^~]+)~~/u' => '<del>$1</del>',
            '/(?<!\*)\*([^*]+)\*(?!\*)/u' => '<em>$1</em>',
            '/(?<!_)_([^_]+)_(?!_)/u' => '<em>$1</em>',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $escaped) ?? $escaped;
    }
}