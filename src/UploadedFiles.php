<?php
declare(strict_types=1);

namespace Recipe;

final class UploadedFiles
{
    public static function normalize(mixed $upload): array
    {
        if (!is_array($upload) || !array_key_exists('name', $upload)) {
            return [];
        }

        if (!is_array($upload['name'])) {
            return [$upload];
        }

        $files = [];
        foreach (array_keys($upload['name']) as $index) {
            $file = [];
            foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
                $values = $upload[$field] ?? [];
                $file[$field] = is_array($values) ? ($values[$index] ?? null) : null;
            }
            $files[] = $file;
        }

        return $files;
    }
}