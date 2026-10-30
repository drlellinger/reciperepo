<?php
declare(strict_types=1);

namespace Recipe;

use Dompdf\Dompdf;
use Dompdf\Options;

final class RecipePdfRenderer
{
    private const MAX_REMOTE_IMAGES = 6;
    private const MAX_REMOTE_IMAGE_BYTES = 8388608;

    public static function render(array $item, array $appConfig, string $publicRoot): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', $publicRoot);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::document($item, $appConfig, $publicRoot), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public static function renderBlocks(array $blocks, string $publicRoot, int &$remainingRemoteImages): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $caption = isset($block['caption']) ? '<figcaption>' . self::escape($block['caption']) . '</figcaption>' : '';
            $duration = isset($block['duration'])
                ? '<span class="recipe-duration">' . self::durationIcon() . ' ' . self::escape(str_replace(' min', ' Min.', RecipeValidator::formatDuration($block['duration']))) . '</span>'
                : '';
            $waiting = isset($block['waiting'])
                ? '<span class="recipe-waiting">Wartezeit: ' . self::escape(str_replace(' min', ' Min.', RecipeValidator::formatDuration($block['waiting']))) . '</span>'
                : '';
            $times = $duration !== '' || $waiting !== '' ? '<div class="recipe-times">' . $duration . $waiting . '</div>' : '';
            switch ($block['type']) {
                case 'heading':
                    $html .= '<div class="recipe-section-heading"><h2>' . self::escape($block['text']) . '</h2>' . $times . '</div>';
                    break;
                case 'text':
                    $html .= '<div class="recipe-block"><div class="recipe-block-content"><p class="recipe-copy">' . nl2br(self::escape($block['text']), false) . '</p></div>' . $times . '</div>';
                    break;
                case 'table':
                    $html .= '<div class="recipe-block"><div class="recipe-block-content"><table>';
                    if (isset($block['caption'])) {
                        $html .= '<caption>' . self::escape($block['caption']) . '</caption>';
                    }
                    $html .= '<thead><tr>';
                    foreach ($block['headers'] as $header) {
                        $html .= '<th>' . self::escape($header) . '</th>';
                    }
                    $html .= '</tr></thead><tbody>';
                    foreach ($block['rows'] as $row) {
                        $html .= '<tr>';
                        foreach ($row as $cell) {
                            $html .= '<td>' . self::escape($cell) . '</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table></div>' . $times . '</div>';
                    break;
                case 'image':
                    $imageSource = self::imageSource($block['url'], $publicRoot, $remainingRemoteImages);
                    $html .= '<div class="recipe-block"><div class="recipe-block-content"><figure class="recipe-media">';
                    if ($imageSource !== null) {
                        $html .= '<img src="' . self::escape($imageSource) . '" alt="' . self::escape($block['alt']) . '">';
                    } else {
                        $html .= '<p class="media-note">' . self::escape($block['alt']) . '</p>';
                    }
                    $html .= $caption . '</figure></div>' . $times . '</div>';
                    break;
                case 'video':
                case 'youtube':
                    $mediaLabel = $block['caption'] ?? 'Medieninhalt';
                    $html .= '<div class="recipe-block"><div class="recipe-block-content"><p class="recipe-link"><a href="' . self::escape($block['url']) . '">' . self::escape($mediaLabel) . '</a></p></div>' . $times . '</div>';
                    break;
                case 'audio':
                    $html .= '<div class="recipe-block"><div class="recipe-block-content"><p class="media-note">' . self::escape($block['caption'] ?? 'Medieninhalt') . '</p></div>' . $times . '</div>';
                    break;
                case 'link':
                    $html .= '<div class="recipe-block"><div class="recipe-block-content"><p class="recipe-link"><a href="' . self::escape($block['url']) . '">' . self::escape($block['label']) . '</a></p></div>' . $times . '</div>';
                    break;
            }
        }

        return $html;
    }

    private static function document(array $item, array $appConfig, string $publicRoot): string
    {
        $remainingRemoteImages = self::MAX_REMOTE_IMAGES;
        $logoPath = is_string($appConfig['logo_image'] ?? null)
            ? self::imageSource($appConfig['logo_image'], $publicRoot, $remainingRemoteImages)
            : null;
        $brandMark = $logoPath !== null
            ? '<img class="brand-logo" src="' . self::escape($logoPath) . '" alt="' . self::escape($appConfig['logo_alt'] ?? '') . '">'
            : '<span class="brand-mark">' . self::escape($appConfig['logo_text'] ?? '') . '</span>';

        $tags = '';
        foreach ($item['recipe']['tags'] ?? [] as $tag) {
            $tags .= '<span class="tag">' . self::escape($tag) . '</span>';
        }

        $totalDuration = RecipeValidator::totalDuration($item['recipe']);
        $totalWaiting = RecipeValidator::totalWaiting($item['recipe']);
        $summaryTimes = '';
        if ($totalDuration > 0 || $totalWaiting > 0) {
            $summaryTimes .= '<div class="recipe-summary-times">';
            if ($totalDuration > 0) {
                $summaryTimes .= '<span class="recipe-duration">' . self::durationIcon() . ' Gesamtdauer: circa ' . self::escape(str_replace(' min', ' Min.', RecipeValidator::formatDuration($totalDuration))) . '</span>';
            }
            if ($totalWaiting > 0) {
                $summaryTimes .= '<span class="recipe-waiting">Gesamtwartezeit: ' . self::escape(str_replace(' min', ' Min.', RecipeValidator::formatDuration($totalWaiting))) . '</span>';
            }
            $summaryTimes .= '</div>';
        }

        return '<!doctype html>
<html lang="de"><head><meta charset="UTF-8"><style>
@page { size: A4 portrait; margin: 17mm 18mm 19mm; }
body { color: #1d312a; font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.55; }
.brand { padding-bottom: 12px; border-bottom: 1px solid #d5dfd8; color: #194b38; font-size: 12pt; font-weight: bold; }
.brand-mark, .brand-logo { display: inline-block; width: 28px; height: 28px; margin-right: 7px; vertical-align: middle; }
.brand-mark { display: inline-block; border-radius: 50%; background: #194b38; color: #fff; font-size: 15pt; line-height: 28px; text-align: center; }
.brand-logo { object-fit: contain; }
.recipe-heading { padding: 24px 0 17px; border-bottom: 1px solid #d5dfd8; }
.tags { margin-bottom: 8px; }
.tag { display: inline-block; margin: 0 5px 4px 0; padding: 3px 8px; border-radius: 10px; background: #e5eee7; color: #244d39; font-size: 8pt; }
h1 { margin: 0; color: #1d312a; font-size: 27pt; line-height: 1.15; }
.recipe-content { padding-top: 15px; }
h2 { margin: 22px 0 9px; color: #194b38; font-size: 16pt; page-break-after: avoid; }
.recipe-section-heading { display: table; width: 100%; }
.recipe-section-heading h2, .recipe-section-heading .recipe-times { display: table-cell; vertical-align: middle; }
.recipe-section-heading .recipe-times { width: 1%; padding-left: 12px; text-align: right; white-space: nowrap; }
.recipe-block { display: table; width: 100%; }
.recipe-block-content, .recipe-block > .recipe-times { display: table-cell; vertical-align: middle; }
.recipe-block > .recipe-times { width: 1%; padding-left: 12px; color: #194b38; font-weight: bold; text-align: right; white-space: nowrap; }
.recipe-times, .recipe-summary-times { display: inline-flex; flex-direction: column; gap: 4px; }
.recipe-summary-times { margin-top: 10px; }
.recipe-duration, .recipe-waiting { display: inline-block; padding: 3px 7px; border: 1px solid #d5dfd8; border-radius: 10px; white-space: nowrap; }
.recipe-duration { background: #e5eee7; color: #194b38; }
.recipe-waiting { border-color: #e5d7a8; background: #f7efd7; color: #765518; }
.duration-icon { width: 9pt; height: 9pt; vertical-align: -1pt; }
.recipe-copy { margin: 0 0 16px; line-height: 1.7; }
table { width: 100%; margin: 15px 0 20px; border-collapse: collapse; page-break-inside: avoid; }
caption { padding: 6px; color: #194b38; font-weight: bold; text-align: left; }
th, td { padding: 7px 9px; border-bottom: 1px solid #d5dfd8; text-align: left; }
th { background: #e9f0ea; color: #194b38; }
tr { page-break-inside: avoid; }
.recipe-media { margin: 16px 0; page-break-inside: avoid; }
.recipe-media img { display: block; max-width: 100%; max-height: 145mm; }
figcaption, .media-note { color: #5f7067; font-size: 9pt; }
.recipe-link { margin: 12px 0; }
a { color: #28664e; }
</style></head><body>
<header class="brand">' . $brandMark . '<span>' . self::escape($appConfig['site_name'] ?? '') . '</span></header>
<section class="recipe-heading"><div class="tags">' . $tags . '</div><h1>' . self::escape($item['recipe']['title']) . '</h1>' . $summaryTimes . '</section>
<main class="recipe-content">' . self::renderBlocks($item['recipe']['blocks'], $publicRoot, $remainingRemoteImages) . '</main>
</body></html>';
    }

    private static function imageSource(string $url, string $publicRoot, int &$remainingRemoteImages): ?string
    {
        $localPath = self::localImagePath($url, $publicRoot);
        if ($localPath !== null) {
            $mimeType = mime_content_type($localPath);
            if ($mimeType === 'image/svg+xml') {
                $svg = file_get_contents($localPath);
                return is_string($svg) ? self::svgDataUri($svg) : null;
            }
            if ($mimeType === 'image/jpeg') {
                $imageBytes = file_get_contents($localPath);
                if (is_string($imageBytes)) {
                    $orientedImage = self::normalizeJpegOrientation($imageBytes, $localPath);
                    if ($orientedImage !== $imageBytes) {
                        return 'data:image/jpeg;base64,' . base64_encode($orientedImage);
                    }
                }
            }
            return 'file://' . $localPath;
        }

        return self::remoteImageDataUri($url, $remainingRemoteImages);
    }

    private static function remoteImageDataUri(string $url, int &$remainingRemoteImages): ?string
    {
        if (!function_exists('curl_init') || $remainingRemoteImages <= 0) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)
            || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $scheme = strtolower($parts['scheme']);
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if ($port !== ($scheme === 'https' ? 443 : 80) || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        $addresses = gethostbynamel($host);
        if (!is_array($addresses) || $addresses === []) {
            return null;
        }
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return null;
            }
        }

        $remainingRemoteImages--;
        $imageBytes = '';
        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }

        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $addresses[0]],
            CURLOPT_USERAGENT => 'RecipeCollection-PDF/1.0',
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$imageBytes): int {
                $length = strlen($chunk);
                if (strlen($imageBytes) + $length > self::MAX_REMOTE_IMAGE_BYTES) {
                    return 0;
                }
                $imageBytes .= $chunk;
                return $length;
            },
        ];
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        $responseCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($result === false || $responseCode !== 200 || $imageBytes === '') {
            return null;
        }

        $svgDataUri = self::svgDataUri($imageBytes);
        if ($svgDataUri !== null) {
            return $svgDataUri;
        }

        $imageInfo = getimagesizefromstring($imageBytes);
        $mimeType = is_array($imageInfo) ? ($imageInfo['mime'] ?? '') : '';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes, true)
            || (int) ($imageInfo[0] ?? 0) * (int) ($imageInfo[1] ?? 0) > 40000000) {
            return null;
        }

        if ($mimeType === 'image/jpeg') {
            $imageBytes = self::normalizeJpegOrientation($imageBytes);
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageBytes);
    }

    private static function normalizeJpegOrientation(string $imageBytes, ?string $imagePath = null): string
    {
        $temporaryPath = null;
        if ($imagePath === null) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'recipe-exif-');
            if ($temporaryPath === false || file_put_contents($temporaryPath, $imageBytes) === false) {
                if (is_string($temporaryPath) && is_file($temporaryPath)) {
                    unlink($temporaryPath);
                }
                return $imageBytes;
            }
            $imagePath = $temporaryPath;
        }

        try {
            $metadata = @exif_read_data($imagePath, 'IFD0', true);
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $orientation = (int) ($metadata['IFD0']['Orientation'] ?? $metadata['Orientation'] ?? 1);
        if ($orientation < 2 || $orientation > 8) {
            return $imageBytes;
        }

        $image = @imagecreatefromstring($imageBytes);
        if ($image === false) {
            return $imageBytes;
        }

        $rotation = match ($orientation) {
            3 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };
        $flip = match ($orientation) {
            2, 5 => IMG_FLIP_HORIZONTAL,
            4 => IMG_FLIP_VERTICAL,
            7 => IMG_FLIP_HORIZONTAL,
            default => null,
        };

        if ($rotation !== 0) {
            $background = imagecolorallocate($image, 255, 255, 255);
            $rotatedImage = imagerotate($image, $rotation, $background);
            if ($rotatedImage === false) {
                imagedestroy($image);
                return $imageBytes;
            }
            imagedestroy($image);
            $image = $rotatedImage;
        }

        if ($flip !== null && !imageflip($image, $flip)) {
            imagedestroy($image);
            return $imageBytes;
        }

        ob_start();
        $encoded = imagejpeg($image, null, 90);
        $orientedBytes = ob_get_clean();
        imagedestroy($image);

        return $encoded && is_string($orientedBytes) ? $orientedBytes : $imageBytes;
    }

    private static function svgDataUri(string $svg): ?string
    {
        if ($svg === '' || strlen($svg) > self::MAX_REMOTE_IMAGE_BYTES
            || preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            return null;
        }

        $document = new \DOMDocument();
        $document->resolveExternals = false;
        $document->substituteEntities = false;
        $previousErrorMode = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        $root = $document->documentElement;
        if (!$loaded || $document->doctype !== null || !($root instanceof \DOMElement)
            || strtolower($root->localName) !== 'svg'
            || $root->namespaceURI !== 'http://www.w3.org/2000/svg') {
            return null;
        }

        self::sanitizeSvgElement($root);
        $safeSvg = $document->saveXML($root);
        return is_string($safeSvg) ? 'data:image/svg+xml;base64,' . base64_encode($safeSvg) : null;
    }

    private static function sanitizeSvgElement(\DOMElement $element): void
    {
        $allowedElements = [
            'svg', 'g', 'path', 'circle', 'rect', 'ellipse', 'line', 'polyline', 'polygon', 'image',
            'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'pattern',
            'use', 'symbol', 'title', 'desc', 'text', 'tspan',
        ];
        $allowedAttributes = [
            'xmlns', 'xmlns:xlink', 'version', 'viewbox', 'width', 'height', 'preserveaspectratio',
            'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap',
            'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset',
            'stroke-opacity', 'opacity', 'transform', 'd', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
            'cx', 'cy', 'r', 'rx', 'ry', 'points', 'offset', 'stop-color', 'stop-opacity',
            'gradientunits', 'gradienttransform', 'spreadmethod', 'clip-path', 'clip-rule',
            'mask', 'patternunits', 'patterntransform', 'id', 'href', 'xlink:href', 'font-family',
            'font-size', 'font-weight', 'text-anchor', 'letter-spacing', 'dominant-baseline',
        ];

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if ($attribute === null) {
                continue;
            }
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');
            $isFragmentReference = preg_match('/\A#[A-Za-z_][A-Za-z0-9_.:-]*\z/', $value) === 1;
            $isEmbeddedImage = preg_match('/\Adata:image\/(?:png|jpeg|gif|webp);base64,[A-Za-z0-9+\/\s]+=*\z/i', $value) === 1;
            $isLocalUrl = preg_match('/\Aurl\(\s*#[A-Za-z_][A-Za-z0-9_.:-]*\s*\)\z/i', $value) === 1;
            if (!in_array($name, $allowedAttributes, true)
                || (in_array($name, ['href', 'xlink:href'], true) && !$isFragmentReference && !$isEmbeddedImage)
                || (stripos($value, 'url(') !== false && !$isLocalUrl)) {
                $element->removeAttributeNode($attribute);
            }
        }

        for ($child = $element->firstChild; $child !== null;) {
            $next = $child->nextSibling;
            if ($child instanceof \DOMElement) {
                if (in_array(strtolower($child->localName), $allowedElements, true)) {
                    self::sanitizeSvgElement($child);
                } else {
                    $element->removeChild($child);
                }
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                $element->removeChild($child);
            }
            $child = $next;
        }
    }

    private static function localImagePath(string $url, string $publicRoot): ?string
    {
        if (preg_match('~\Ahttps?://~i', $url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $realRoot = realpath($publicRoot);
        $realPath = is_string($path) && $realRoot !== false
            ? realpath($realRoot . DIRECTORY_SEPARATOR . ltrim($path, '/'))
            : false;
        if ($realRoot === false || $realPath === false
            || !str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)
            || !is_file($realPath)) {
            return null;
        }

        $mimeType = mime_content_type($realPath);
        return is_string($mimeType) && str_starts_with($mimeType, 'image/') ? $realPath : null;
    }

    private static function escape(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function durationIcon(): string
    {
        return '<svg class="duration-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8" fill="#194b38"/><path d="M12 13V8m0 5 3 2M9 3h6M12 3v2" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/></svg>';
    }
}