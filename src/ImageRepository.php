<?php
declare(strict_types=1);

namespace Recipe;

use RuntimeException;

final class ImageRepository
{
    private const MAX_SIZE = 8388608;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Das Bildverzeichnis konnte nicht angelegt werden.');
        }
    }

    public function save(string $temporaryPath): string
    {
        $size = filesize($temporaryPath);
        if ($size === false || $size < 1 || $size > self::MAX_SIZE) {
            throw new RuntimeException('Bilder müssen größer als 0 Byte und höchstens 8 MB groß sein.');
        }

        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryPath);
        if (!is_string($mimeType) || !isset(self::MIME_EXTENSIONS[$mimeType])) {
            throw new RuntimeException('Erlaubt sind JPEG-, PNG-, GIF- und WebP-Bilder.');
        }

        $imageInfo = getimagesize($temporaryPath);
        if ($imageInfo === false || ($imageInfo['mime'] ?? null) !== $mimeType) {
            throw new RuntimeException('Die hochgeladene Datei ist kein gültiges Bild.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mimeType];
        $temporaryTarget = tempnam($this->directory, '.image-');
        if ($temporaryTarget === false) {
            throw new RuntimeException('Eine temporäre Bilddatei konnte nicht angelegt werden.');
        }

        try {
            if (!copy($temporaryPath, $temporaryTarget)) {
                throw new RuntimeException('Das Bild konnte nicht gespeichert werden.');
            }
            chmod($temporaryTarget, 0644);
            if (!rename($temporaryTarget, $this->directory . '/' . $filename)) {
                throw new RuntimeException('Das Bild konnte nicht veröffentlicht werden.');
            }
        } finally {
            if (is_file($temporaryTarget)) {
                unlink($temporaryTarget);
            }
        }

        return '/uploads/images/' . $filename;
    }

    public function all(): array
    {
        $images = [];
        foreach (glob($this->directory . '/*') ?: [] as $path) {
            if (!is_file($path) || !preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|gif|webp)\z/', basename($path))) {
                continue;
            }
            $images[] = [
                'url' => '/uploads/images/' . basename($path),
                'modifiedAt' => (int) filemtime($path),
            ];
        }
        usort($images, static fn (array $left, array $right): int => $right['modifiedAt'] <=> $left['modifiedAt']);
        return $images;
    }
}