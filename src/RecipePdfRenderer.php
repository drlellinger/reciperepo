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
            switch ($block['type']) {
                case 'heading':
                    $html .= '<h2>' . self::escape($block['text']) . '</h2>';
                    break;
                case 'text':
                    $html .= '<p class="recipe-copy">' . nl2br(self::escape($block['text']), false) . '</p>';
                    break;
                case 'table':
                    $html .= '<table>';
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
                    $html .= '</tbody></table>';
                    break;
                case 'image':
                    $imageSource = self::imageSource($block['url'], $publicRoot, $remainingRemoteImages);
                    $html .= '<figure class="recipe-media">';
                    if ($imageSource !== null) {
                        $html .= '<img src="' . self::escape($imageSource) . '" alt="' . self::escape($block['alt']) . '">';
                    } else {
                        $html .= '<p class="media-note">' . self::escape($block['alt']) . '</p>';
                    }
                    $html .= $caption . '</figure>';
                    break;
                case 'video':
                case 'audio':
                    $html .= '<p class="media-note">' . self::escape($block['caption'] ?? 'Medieninhalt') . '</p>';
                    break;
                case 'link':
                    $html .= '<p class="recipe-link"><a href="' . self::escape($block['url']) . '">' . self::escape($block['label']) . '</a></p>';
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
<section class="recipe-heading"><div class="tags">' . $tags . '</div><h1>' . self::escape($item['recipe']['title']) . '</h1></section>
<main class="recipe-content">' . self::renderBlocks($item['recipe']['blocks'], $publicRoot, $remainingRemoteImages) . '</main>
</body></html>';
    }

    private static function imageSource(string $url, string $publicRoot, int &$remainingRemoteImages): ?string
    {
        $localPath = self::localImagePath($url, $publicRoot);
        if ($localPath !== null) {
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

        $imageInfo = getimagesizefromstring($imageBytes);
        $mimeType = is_array($imageInfo) ? ($imageInfo['mime'] ?? '') : '';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes, true)
            || (int) ($imageInfo[0] ?? 0) * (int) ($imageInfo[1] ?? 0) > 40000000) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageBytes);
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
}