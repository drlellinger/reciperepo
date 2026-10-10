<?php
declare(strict_types=1);

use Recipe\RecipeRepository;
use Recipe\RecipeValidator;

require dirname(__DIR__) . '/vendor/autoload.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https: http:; media-src 'self' https: http:; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

$root = dirname(__DIR__);
$appConfig = require $root . '/config/app.php';
$repository = new RecipeRepository($root . '/recipes');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isAdmin = $path === '/admin' || str_starts_with($path, '/admin/');

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

function renderPage(string $template, string $title, array $variables = []): never
{
    global $appConfig;
    $variables['appConfig'] = $appConfig;
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

function renderBlocks(array $blocks): string
{
    $html = '';
    foreach ($blocks as $block) {
        $caption = isset($block['caption']) ? '<figcaption>' . escape($block['caption']) . '</figcaption>' : '';
        switch ($block['type']) {
            case 'text':
                $html .= '<div class="recipe-copy">' . nl2br(escape($block['text']), false) . '</div>';
                break;
            case 'table':
                $html .= '<div class="table-wrap"><table><thead><tr>';
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
                $html .= '</tbody></table></div>';
                break;
            case 'image':
                $html .= '<figure class="recipe-media"><img src="' . escape($block['url']) . '" alt="' . escape($block['alt']) . '" loading="lazy">' . $caption . '</figure>';
                break;
            case 'video':
                $html .= '<figure class="recipe-media"><video controls preload="metadata" src="' . escape($block['url']) . '"></video>' . $caption . '</figure>';
                break;
            case 'audio':
                $html .= '<figure class="recipe-media"><audio controls preload="metadata" src="' . escape($block['url']) . '"></audio>' . $caption . '</figure>';
                break;
            case 'link':
                $html .= '<p class="recipe-link"><a href="' . escape($block['url']) . '" target="_blank" rel="noopener noreferrer">' . escape($block['label']) . ' ↗</a></p>';
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

if ($method === 'POST' && $isAdmin) {
    if (!validCsrfToken()) {
        redirectToAdmin('Sitzung abgelaufen. Bitte erneut versuchen.', true);
    }

    try {
        if ($path === '/admin/upload') {
            $upload = $_FILES['recipe'] ?? null;
            if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) {
                throw new RuntimeException('Die YAML-Datei konnte nicht hochgeladen werden.');
            }
            if (($upload['size'] ?? 0) > 1048576) {
                throw new RuntimeException('Die YAML-Datei darf höchstens 1 MB groß sein.');
            }
            if (!preg_match('/\.ya?ml\z/i', (string) ($upload['name'] ?? ''))) {
                throw new RuntimeException('Bitte eine Datei mit der Endung .yaml oder .yml auswählen.');
            }
            $contents = file_get_contents($upload['tmp_name']);
            if ($contents === false) {
                throw new RuntimeException('Die hochgeladene Datei konnte nicht gelesen werden.');
            }
            $recipe = RecipeValidator::parse($contents);
            $repository->save($contents, $recipe);
            redirectToAdmin('Rezept gespeichert.');
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

if ($path === '/' && $method === 'GET') {
    $allItems = $repository->all();
    $sort = $_GET['sort'] ?? 'name';
    $sort = is_string($sort) && in_array($sort, ['name', 'date', 'category'], true) ? $sort : 'name';
    $selectedTag = $_GET['tag'] ?? '';
    $selectedTag = is_string($selectedTag) ? $selectedTag : '';
    $groupBy = $_GET['group'] ?? 'none';
    $groupBy = $groupBy === 'tag' ? 'tag' : 'none';

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
        } else {
            $comparison = strnatcasecmp($left['recipe']['title'], $right['recipe']['title']);
        }

        return $comparison !== 0 ? $comparison : strnatcasecmp($left['recipe']['title'], $right['recipe']['title']);
    });

    $groups = [];
    if ($groupBy === 'tag') {
        foreach ($items as $item) {
            $itemTags = $item['recipe']['tags'] ?? [];
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
        'totalCount' => count($allItems),
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
    renderPage('admin', 'Verwaltung', [
        'items' => $repository->all(),
        'csrfToken' => $_SESSION['_csrf'],
        'flash' => takeFlash(),
    ]);
}

if (preg_match('~\A/recipe/([a-z0-9]+(?:-[a-z0-9]+)*)\z~', $path, $matches) && $method === 'GET') {
    $item = $repository->find($matches[1]);
    if ($item !== null) {
        renderPage('recipe', $item['recipe']['title'], [
            'item' => $item,
            'blocksHtml' => renderBlocks($item['recipe']['blocks']),
        ]);
    }
}

http_response_code(404);
renderPage('not-found', 'Nicht gefunden');