<?php
declare(strict_types=1);

namespace Recipe;

use RuntimeException;

final class ImageSanitizer
{
    public static function write(string $sourcePath, string $targetPath, string $mimeType): void
    {
        if ($mimeType === 'image/gif') {
            if (!copy($sourcePath, $targetPath)) {
                throw new RuntimeException('Das Bild konnte nicht gespeichert werden.');
            }
            return;
        }

        $imageBytes = file_get_contents($sourcePath);
        $image = is_string($imageBytes) ? @imagecreatefromstring($imageBytes) : false;
        if ($image === false) {
            throw new RuntimeException('Das Bild konnte nicht verarbeitet werden.');
        }

        if ($mimeType === 'image/jpeg') {
            $image = self::applyJpegOrientation($image, $sourcePath);
        } elseif ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $targetPath, 90),
            'image/png' => imagepng($image, $targetPath, 6),
            'image/webp' => imagewebp($image, $targetPath, 90),
            default => false,
        };
        imagedestroy($image);

        if (!$saved) {
            throw new RuntimeException('Das Bild konnte nicht ohne Metadaten gespeichert werden.');
        }
    }

    private static function applyJpegOrientation(\GdImage $image, string $imagePath): \GdImage
    {
        $metadata = @exif_read_data($imagePath, 'IFD0', true);
        $orientation = (int) ($metadata['IFD0']['Orientation'] ?? $metadata['Orientation'] ?? 1);

        $flip = match ($orientation) {
            2, 5, 7 => IMG_FLIP_HORIZONTAL,
            4 => IMG_FLIP_VERTICAL,
            default => null,
        };
        if ($flip !== null) {
            imageflip($image, $flip);
        }

        $rotation = match ($orientation) {
            3 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };
        if ($rotation === 0) {
            return $image;
        }

        $background = imagecolorallocate($image, 255, 255, 255);
        $rotated = imagerotate($image, $rotation, $background);
        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);
        return $rotated;
    }
}