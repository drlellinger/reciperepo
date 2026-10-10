<?php
declare(strict_types=1);

namespace Recipe;

use RuntimeException;

final class RecipeRepository
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Das Rezeptverzeichnis konnte nicht angelegt werden.');
        }
    }

    public function all(): array
    {
        $recipes = [];
        foreach (glob($this->directory . '/*.yaml') ?: [] as $path) {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException('Eine Rezeptdatei konnte nicht gelesen werden.');
            }
            $recipes[] = [
                'slug' => basename($path, '.yaml'),
                'yaml' => $contents,
                'recipe' => RecipeValidator::parse($contents),
                'modifiedAt' => (int) filemtime($path),
            ];
        }

        return $recipes;
    }

    public function find(string $slug): ?array
    {
        $path = $this->path($slug);
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Die Rezeptdatei konnte nicht gelesen werden.');
        }

        return [
            'slug' => $slug,
            'yaml' => $contents,
            'recipe' => RecipeValidator::parse($contents),
            'modifiedAt' => (int) filemtime($path),
        ];
    }

    public function save(string $contents, array $recipe): string
    {
        $slug = RecipeValidator::slug($recipe['title']);
        $path = $this->path($slug);
        $existing = $this->find($slug);
        if ($existing !== null && RecipeValidator::slug($existing['recipe']['title']) !== $slug) {
            throw new RuntimeException('Der Rezeptname ist nicht eindeutig.');
        }

        $temporaryPath = tempnam($this->directory, '.recipe-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Eine temporäre Rezeptdatei konnte nicht angelegt werden.');
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) !== strlen($contents)) {
                throw new RuntimeException('Das Rezept konnte nicht vollständig gespeichert werden.');
            }
            chmod($temporaryPath, 0640);
            if (!rename($temporaryPath, $path)) {
                throw new RuntimeException('Das Rezept konnte nicht gespeichert werden.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        return $slug;
    }

    public function delete(string $slug): bool
    {
        $path = $this->path($slug);
        return is_file($path) && unlink($path);
    }

    private function path(string $slug): string
    {
        if (!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug)) {
            throw new RuntimeException('Ungültiger Rezeptname.');
        }

        return $this->directory . '/' . $slug . '.yaml';
    }
}