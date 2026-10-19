<?php
declare(strict_types=1);

use Recipe\RecipeRepository;
use Recipe\RecipeValidator;
use Recipe\ImageRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https: http:; media-src 'self' https: http:; frame-src https://www.youtube-nocookie.com; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

$root = dirname(__DIR__);
$appConfig = require $root . '/config/app.php';
$repository = new RecipeRepository($root . '/recipes');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isAdmin = $path === '/admin' || str_starts_with($path, '/admin/');
$errorPages = [
    '401' => [
        'title' => 'Anmeldung erforderlich',
        'message' => 'Für diese Seite ist eine Anmeldung erforderlich.',
    ],
    '403' => [
        'title' => 'Zugriff verweigert',
        'message' => 'Du hast keine Berechtigung, diese Seite aufzurufen.',
    ],
    '413' => [
        'title' => 'Datei zu groß',
        'message' => 'Die hochgeladene Datei ist zu groß. Bitte wähle eine kleinere Datei aus.',
    ],
    '500' => [
        'title' => 'Ein Fehler ist aufgetreten',
        'message' => 'Die Seite konnte gerade nicht geladen werden. Bitte versuche es später erneut.',
    ],
];

if ($isAdmin) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function escape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function siteOrigin(): string
{
    global $appConfig;
    $configuredUrl = $appConfig['site_url'] ?? null;
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $candidate = is_string($configuredUrl) && trim($configuredUrl) !== ''
        ? trim($configuredUrl)
        : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    $parts = parse_url($candidate);
    if (!is_array($parts)
        || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])
        || !in_array($parts['path'] ?? '', ['', '/'], true)
        || filter_var($candidate, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    return strtolower($parts['scheme']) . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
}

function absolutePublicUrl(string $url, string $origin): string
{
    if (preg_match('~\Ahttps?://~i', $url)) {
        return $url;
    }
    return $origin . '/' . ltrim($url, '/');
}

function renderPage(string $template, string $title, array $variables = []): never
{
    global $appConfig, $path, $isAdmin;
    $variables['appConfig'] = $appConfig;
    $variables['siteOrigin'] = siteOrigin();
    $variables['pageUrl'] = $variables['siteOrigin'] . $path;
    $variables['description'] ??= $title;
    $variables['socialType'] ??= 'website';
    extract($variables, EXTR_SKIP);
    require dirname(__DIR__) . '/templates/header.php';
    require dirname(__DIR__) . '/templates/' . $template . '.php';
    require dirname(__DIR__) . '/templates/footer.php';
    exit;
}

function redirectToAdmin(string $message, bool $isError = false): never
{
    $_SESSION['flash'] = ['message' => $message, 'error' => $isError];
    header('Location: /admin', true, 303);
    exit;
}

function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function adminIsAuthenticated(): bool
{
    return is_string($_SERVER['PHP_AUTH_USER'] ?? null) && trim($_SERVER['PHP_AUTH_USER']) !== ''
        || is_string($_SERVER['REMOTE_USER'] ?? null) && trim($_SERVER['REMOTE_USER']) !== '';
}

if (preg_match('~\A/error/(401|403|413|500)\z~', $path, $matches)) {
    $errorCode = $matches[1];
    http_response_code((int) $errorCode);
    if ($errorCode === '401') {
        header('WWW-Authenticate: Basic realm="Recipe administration"');
    }
    renderPage('error', $errorCode . ' · ' . $errorPages[$errorCode]['title'], [
        'errorCode' => $errorCode,
        'errorTitle' => $errorPages[$errorCode]['title'],
        'errorMessage' => $errorPages[$errorCode]['message'],
    ]);
}

if ($isAdmin && !adminIsAuthenticated()) {
    http_response_code(401);
    header('WWW-Authenticate: Basic realm="Recipe administration"');
    renderPage('error', '401 · ' . $errorPages['401']['title'], [
        'errorCode' => '401',
        'errorTitle' => $errorPages['401']['title'],
        'errorMessage' => $errorPages['401']['message'],
    ]);
}

function renderBlocks(array $blocks): string
{
    $html = '';
    foreach ($blocks as $block) {
        $caption = isset($block['caption']) ? '<figcaption>' . escape($block['caption']) . '</figcaption>' : '';
        $duration = isset($block['duration'])
            ? '<span class="recipe-duration">⏱ ' . escape(RecipeValidator::formatDuration($block['duration'])) . '</span>'
            : '';
        switch ($block['type']) {
            case 'heading':
                $html .= '<div class="recipe-section-heading"><h2>' . escape($block['text']) . '</h2>' . $duration . '</div>';
                break;
            case 'text':
                $html .= '<div class="recipe-block"><div class="recipe-copy">' . nl2br(escape($block['text']), false) . '</div>' . $duration . '</div>';
                break;
            case 'table':
                $tableCaption = isset($block['caption']) ? '<caption>' . escape($block['caption']) . '</caption>' : '';
                $tableLabel = 'Tabellenbereich: ' . ($block['caption'] ?? 'Rezepttabelle');
                $html .= '<div class="recipe-block"><div class="table-wrap" role="region" aria-label="' . escape($tableLabel) . '" tabindex="0"><table>' . $tableCaption . '<thead><tr>';
                foreach ($block['headers'] as $header) {
                    $html .= '<th scope="col">' . escape($header) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($block['rows'] as $row) {
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $html .= '<td>' . escape($cell) . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>' . $duration . '</div>';
                break;
            case 'image':
                $html .= '<div class="recipe-block"><figure class="recipe-media"><img src="' . escape($block['url']) . '" alt="' . escape($block['alt']) . '" loading="lazy">' . $caption . '</figure>' . $duration . '</div>';
                break;
            case 'video':
                $html .= '<div class="recipe-block"><figure class="recipe-media"><video aria-label="' . escape($block['caption'] ?? 'Rezeptvideo') . '" controls preload="metadata" src="' . escape($block['url']) . '"></video>' . $caption . '</figure>' . $duration . '</div>';
                break;
            case 'youtube':
                $embedUrl = RecipeValidator::youtubeEmbedUrl($block['url']);
                $html .= '<div class="recipe-block"><figure class="recipe-media"><div class="recipe-video-embed"><iframe src="' . escape($embedUrl) . '" title="' . escape($block['caption'] ?? 'YouTube-Video') . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>' . $caption . '</figure>' . $duration . '</div>';
                break;
            case 'audio':
                $html .= '<div class="recipe-block"><figure class="recipe-media"><audio aria-label="' . escape($block['caption'] ?? 'Rezeptaudio') . '" controls preload="metadata" src="' . escape($block['url']) . '"></audio>' . $caption . '</figure>' . $duration . '</div>';
                break;
            case 'link':
                $html .= '<div class="recipe-block"><p class="recipe-link"><a href="' . escape($block['url']) . '" target="_blank" rel="noopener noreferrer">' . escape($block['label']) . ' <span class="visually-hidden">(öffnet in neuem Tab)</span><span aria-hidden="true">↗</span></a></p>' . $duration . '</div>';
                break;
        }
    }
    return $html;
}

function validCsrfToken(): bool
{
    $provided = $_POST['_csrf'] ?? '';
    $expected = $_SESSION['_csrf'] ?? '';
    return is_string($provided) && is_string($expected) && $expected !== '' && hash_equals($expected, $provided);
}

function uploadErrorMessage(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE => 'Die Datei ist größer als das vom Server erlaubte Limit. Bitte upload_max_filesize und post_max_size prüfen.',
        UPLOAD_ERR_FORM_SIZE => 'Die Datei überschreitet das vom Formular erlaubte Limit.',
        UPLOAD_ERR_PARTIAL => 'Der Upload wurde nur teilweise übertragen. Bitte erneut versuchen.',
        UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei ausgewählt.',
        UPLOAD_ERR_CANT_WRITE => 'Das temporäre Verzeichnis für den Upload ist nicht beschreibbar.',
        UPLOAD_ERR_EXTENSION => 'Ein PHP-Upload-Filter hat den Upload blockiert.',
        default => 'Das Bild konnte nicht hochgeladen werden.',
    };
}

if ($method === 'POST' && $isAdmin) {
    if (!validCsrfToken()) {
        redirectToAdmin('Sitzung abgelaufen. Bitte erneut versuchen.', true);
    }

    try {
        if ($path === '/admin/upload') {
            $uploads = Recipe\UploadedFiles::normalize($_FILES['recipe'] ?? null);
            if ($uploads === []) {
                throw new RuntimeException('Bitte mindestens eine YAML-Datei auswählen.');
            }

            $savedCount = 0;
            $failures = [];
            foreach ($uploads as $upload) {
                $filename = (string) ($upload['name'] ?? 'Datei');
                try {
                    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        throw new RuntimeException(uploadErrorMessage((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE)));
                    }
                    $temporaryPath = (string) ($upload['tmp_name'] ?? '');
                    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
                        throw new RuntimeException('Die Datei konnte nicht korrekt übertragen werden.');
                    }
                    if (($upload['size'] ?? 0) > 1048576) {
                        throw new RuntimeException('Die YAML-Datei darf höchstens 1 MB groß sein.');
                    }
                    if (!preg_match('/\.ya?ml\z/i', $filename)) {
                        throw new RuntimeException('Erlaubt sind Dateiendungen .yaml und .yml.');
                    }
                    $contents = file_get_contents($temporaryPath);
                    if ($contents === false) {
                        throw new RuntimeException('Die hochgeladene Datei konnte nicht gelesen werden.');
                    }
                    $recipe = RecipeValidator::parse($contents);
                    $repository->save($contents, $recipe);
                    $savedCount++;
                } catch (Throwable $exception) {
                    $failures[] = $filename . ': ' . $exception->getMessage();
                }
            }

            if ($failures !== []) {
                $message = $savedCount > 0 ? $savedCount . ' von ' . count($uploads) . ' Rezepten verarbeitet. ' : '';
                redirectToAdmin($message . 'Fehler: ' . implode(' ', $failures), true);
            }
            redirectToAdmin($savedCount . ' Rezepte wurden importiert oder aktualisiert.');
        }

        if ($path === '/admin/upload-image') {
            $uploads = Recipe\UploadedFiles::normalize($_FILES['image'] ?? null);
            if ($uploads === []) {
                throw new RuntimeException('Bitte mindestens ein Bild auswählen.');
            }

            $imageRepository = new ImageRepository($root . '/public/uploads/images');
            $savedCount = 0;
            $failures = [];
            foreach ($uploads as $upload) {
                $filename = (string) ($upload['name'] ?? 'Datei');
                try {
                    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        throw new RuntimeException(uploadErrorMessage((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE)));
                    }
                    $temporaryPath = (string) ($upload['tmp_name'] ?? '');
                    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
                        throw new RuntimeException('Das Bild konnte nicht korrekt übertragen werden.');
                    }
                    $imageRepository->save($temporaryPath);
                    $savedCount++;
                } catch (Throwable $exception) {
                    $failures[] = $filename . ': ' . $exception->getMessage();
                }
            }

            if ($failures !== []) {
                $message = $savedCount > 0 ? $savedCount . ' von ' . count($uploads) . ' Bildern hochgeladen. ' : '';
                redirectToAdmin($message . 'Fehler: ' . implode(' ', $failures), true);
            }
            redirectToAdmin($savedCount . ' Bilder wurden hochgeladen.');
        }

        if (preg_match('~\A/admin/delete/([a-z0-9]+(?:-[a-z0-9]+)*)\z~', $path, $matches)) {
            if (!$repository->delete($matches[1])) {
                throw new RuntimeException('Das Rezept wurde nicht gefunden.');
            }
            redirectToAdmin('Rezept gelöscht.');
        }
    } catch (Throwable $exception) {
        redirectToAdmin($exception->getMessage(), true);
    }
}

if ($path === '/download' && $method === 'GET') {
    $slug = $_GET['recipe'] ?? '';
    if (!is_string($slug) || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug)) {
        http_response_code(404);
        exit;
    }
    $item = $repository->find($slug);
    if ($item === null) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: application/yaml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $item['slug'] . '.yaml"');
    echo $item['yaml'];
    exit;
}

if (preg_match('~\A/recipe/([a-z0-9]+(?:-[a-z0-9]+)*)/pdf\z~', $path, $matches) && $method === 'GET') {
    $item = $repository->find($matches[1]);
    if ($item === null) {
        http_response_code(404);
        exit;
    }

    $pdf = Recipe\RecipePdfRenderer::render($item, $appConfig, $root . '/public');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $item['slug'] . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

if ($path === '/' && $method === 'GET') {
    $allItems = $repository->all();
    $sort = $_GET['sort'] ?? 'name';
    $sort = is_string($sort) && in_array($sort, ['name', 'date', 'category', 'duration'], true) ? $sort : 'name';
    $selectedTag = $_GET['tag'] ?? '';
    $selectedTag = is_string($selectedTag) ? $selectedTag : '';
    $groupBy = $_GET['group'] ?? 'none';
    $groupBy = $groupBy === 'tag' ? 'tag' : 'none';
    $perPage = $_GET['perPage'] ?? '10';
    $perPage = is_string($perPage) && in_array($perPage, ['5', '10', '20', '50'], true) ? (int) $perPage : 10;
    $page = $_GET['page'] ?? '1';
    $page = max(1, (int) $page);

    $tags = [];
    foreach ($allItems as $item) {
        foreach ($item['recipe']['tags'] ?? [] as $tag) {
            $tags[$tag] = $tag;
        }
    }
    natcasesort($tags);
    $tags = array_values($tags);
    if ($selectedTag !== '' && !in_array($selectedTag, $tags, true)) {
        $selectedTag = '';
    }

    $items = array_values(array_filter($allItems, static fn (array $item): bool => $selectedTag === '' || in_array($selectedTag, $item['recipe']['tags'] ?? [], true)));
    usort($items, static function (array $left, array $right) use ($sort): int {
        if ($sort === 'date') {
            $comparison = $right['modifiedAt'] <=> $left['modifiedAt'];
        } elseif ($sort === 'category') {
            $leftTags = $left['recipe']['tags'] ?? [];
            $rightTags = $right['recipe']['tags'] ?? [];
            natcasesort($leftTags);
            natcasesort($rightTags);
            $comparison = strnatcasecmp((string) (reset($leftTags) ?: ''), (string) (reset($rightTags) ?: ''));
        } elseif ($sort === 'duration') {
            $comparison = RecipeValidator::totalDuration($left['recipe']) <=> RecipeValidator::totalDuration($right['recipe']);
        } else {
            $comparison = strnatcasecmp($left['recipe']['title'], $right['recipe']['title']);
        }

        return $comparison !== 0 ? $comparison : strnatcasecmp($left['recipe']['title'], $right['recipe']['title']);
    });

    $totalCount = count($items);
    $pageCount = $totalCount === 0 ? 1 : max(1, (int) ceil($totalCount / $perPage));
    $page = min($page, $pageCount);
    $visibleStart = $totalCount === 0 ? 0 : ($page - 1) * $perPage + 1;
    $visibleEnd = min($page * $perPage, $totalCount);
    $items = array_slice($items, ($page - 1) * $perPage, $perPage);

    $groups = [];
    if ($groupBy === 'tag') {
        foreach ($items as $item) {
            $itemTags = $item['recipe']['tags'] ?? [];
            if ($itemTags === []) {
                $groups[''][] = $item;
                continue;
            }
            if ($selectedTag !== '') {
                $itemTags = array_values(array_filter($itemTags, static fn (string $tag): bool => $tag !== $selectedTag));
            }
            if ($itemTags === []) {
                $groups[''][] = $item;
                continue;
            }
            foreach ($itemTags as $tag) {
                $groups[$tag][] = $item;
            }
        }
        uksort($groups, static function (string $left, string $right): int {
            if ($left === '') {
                return 1;
            }
            if ($right === '') {
                return -1;
            }
            return strnatcasecmp($left, $right);
        });
    } else {
        $groups = ['' => $items];
    }

    renderPage('list', 'Rezeptsammlung', [
        'items' => $items,
        'totalCount' => $totalCount,
        'visibleStart' => $visibleStart,
        'visibleEnd' => $visibleEnd,
        'page' => $page,
        'pageCount' => $pageCount,
        'perPage' => $perPage,
        'groups' => $groups,
        'tags' => $tags,
        'selectedTag' => $selectedTag,
        'sort' => $sort,
        'groupBy' => $groupBy,
    ]);
}

if ($path === '/admin' && $method === 'GET') {
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    $allItems = $repository->all();
    $search = $_GET['q'] ?? '';
    $search = is_string($search) ? trim($search) : '';
    $sort = $_GET['sort'] ?? 'date';
    $sort = is_string($sort) && in_array($sort, ['name', 'date'], true) ? $sort : 'date';
    $perPage = $_GET['perPage'] ?? '10';
    $perPage = is_string($perPage) && in_array($perPage, ['5', '10', '20', '50'], true) ? (int) $perPage : 10;
    $page = $_GET['page'] ?? '1';
    $page = max(1, (int) $page);
    $items = array_values(array_filter($allItems, static function (array $item) use ($search): bool {
        if ($search === '') {
            return true;
        }

        $haystack = $item['recipe']['title'] . ' ' . $item['slug'] . ' '
            . implode(' ', $item['recipe']['aliases'] ?? []) . ' '
            . implode(' ', $item['recipe']['tags'] ?? []);
        return preg_match('/' . preg_quote($search, '/') . '/iu', $haystack) === 1;
    }));
    usort($items, static function (array $left, array $right) use ($sort): int {
        $comparison = $sort === 'date'
            ? $right['modifiedAt'] <=> $left['modifiedAt']
            : strnatcasecmp($left['recipe']['title'], $right['recipe']['title']);

        return $comparison !== 0 ? $comparison : strnatcasecmp($left['recipe']['title'], $right['recipe']['title']);
    });
    $totalCount = count($items);
    $pageCount = $totalCount === 0 ? 1 : max(1, (int) ceil($totalCount / $perPage));
    $page = min($page, $pageCount);
    $visibleStart = $totalCount === 0 ? 0 : ($page - 1) * $perPage + 1;
    $visibleEnd = min($page * $perPage, $totalCount);
    $items = array_slice($items, ($page - 1) * $perPage, $perPage);
    renderPage('admin', 'Verwaltung', [
        'items' => $items,
        'totalCount' => $totalCount,
        'visibleStart' => $visibleStart,
        'visibleEnd' => $visibleEnd,
        'page' => $page,
        'pageCount' => $pageCount,
        'perPage' => $perPage,
        'search' => $search,
        'sort' => $sort,
        'images' => (new ImageRepository($root . '/public/uploads/images'))->all(),
        'csrfToken' => $_SESSION['_csrf'],
        'flash' => takeFlash(),
    ]);
}

if (preg_match('~\A/recipe/([a-z0-9]+(?:-[a-z0-9]+)*)\z~', $path, $matches) && $method === 'GET') {
    $item = $repository->find($matches[1]);
    if ($item !== null) {
        $socialImage = null;
        $socialImageAlt = '';
        foreach ($item['recipe']['blocks'] as $block) {
            if ($block['type'] === 'image') {
                $socialImage = $block['url'];
                $socialImageAlt = $block['alt'];
                break;
            }
        }
        $socialImage ??= $appConfig['logo_image'] ?? $appConfig['favicon'];
        renderPage('recipe', $item['recipe']['title'], [
            'item' => $item,
            'blocksHtml' => renderBlocks($item['recipe']['blocks']),
            'totalDuration' => RecipeValidator::totalDuration($item['recipe']),
            'description' => $item['recipe']['summary'] ?? $item['recipe']['title'],
            'socialImage' => $socialImage,
            'socialImageAlt' => $socialImageAlt !== '' ? $socialImageAlt : $appConfig['logo_alt'],
            'socialType' => 'article',
        ]);
    }
}

http_response_code(404);
renderPage('not-found', 'Nicht gefunden');