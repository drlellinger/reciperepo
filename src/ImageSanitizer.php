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
            'image/jpeg' => self::writeJpeg($image, $targetPath),
            'image/png' => imagepng($image, $targetPath, 6),
            'image/webp' => imagewebp($image, $targetPath, 90),
            default => false,
        };
        imagedestroy($image);

        if (!$saved) {
            throw new RuntimeException('Das Bild konnte nicht ohne Metadaten gespeichert werden.');
        }
    }

    private static function writeJpeg(\GdImage $image, string $targetPath): bool
    {
        ob_start();
        $encoded = imagejpeg($image, null, 90);
        $bytes = ob_get_clean();
        if (!$encoded || !is_string($bytes)) {
            return false;
        }

        $bytes = self::removeJpegApp1Segments($bytes);
        return file_put_contents($targetPath, $bytes) !== false;
    }

    private static function removeJpegApp1Segments(string $bytes): string
    {
        if (!str_starts_with($bytes, "\xFF\xD8")) {
            return $bytes;
        }

        $result = substr($bytes, 0, 2);
        $offset = 2;
        $length = strlen($bytes);
        while ($offset + 3 < $length && $bytes[$offset] === "\xFF") {
            $marker = ord($bytes[$offset + 1]);
            if ($marker === 0xDA || $marker === 0xD9) {
                $result .= substr($bytes, $offset);
                return $result;
            }

            $segmentLength = unpack('n', substr($bytes, $offset + 2, 2))[1] ?? 0;
            $segmentEnd = $offset + 2 + $segmentLength;
            if ($segmentLength < 2 || $segmentEnd > $length) {
                return $bytes;
            }
            if ($marker !== 0xE1) {
                $result .= substr($bytes, $offset, $segmentEnd - $offset);
            }
            $offset = $segmentEnd;
        }

        return $bytes;
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