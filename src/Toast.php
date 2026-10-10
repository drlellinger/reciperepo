<?php
declare(strict_types=1);

namespace Recipe;

final class Toast
{
    public static function message(?array $flash): ?string
    {
        if (!is_array($flash) || !isset($flash['message'])) {
            return null;
        }

        $message = trim((string) $flash['message']);
        return $message === '' ? null : $message;
    }
}
