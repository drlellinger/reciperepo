<?php
declare(strict_types=1);

function runAdminTests(): void
{
    $llmAuthoringPath = dirname(__DIR__) . '/docs/llm-yaml-authoring.md';
    $llmAuthoring = file_get_contents($llmAuthoringPath);
    check(is_string($llmAuthoring) && $llmAuthoring !== '', 'LLM-Anleitung ist für die Admin-Ansicht verfügbar.');

    $totalCount = 0;
    $csrfToken = 'test-csrf-token';
    $items = [];
    $search = '';
    $sort = 'date';
    $perPage = 10;
    $page = 1;
    $pageCount = 1;
    $visibleStart = 0;
    $visibleEnd = 0;
    $images = [];
    $appConfig = ['version' => require dirname(__DIR__) . '/config/version.php'];

    ob_start();
    require dirname(__DIR__) . '/templates/admin.php';
    $html = ob_get_clean();

    $matched = preg_match(
        '~<button class="button button-copy" type="button" data-copy="([^"]*)" data-copy-message="([^"]*)">LLM-Anleitung kopieren</button>~',
        $html,
        $match,
    );
    check($matched === 1, 'Admin-Ansicht rendert den Kopierbutton für die LLM-Anleitung.');
    check(htmlspecialchars_decode($match[1], ENT_QUOTES) === $llmAuthoring, 'Kopierbutton enthält den vollständigen Inhalt der LLM-Anleitung.');
    check($match[2] === 'Anweisungen für YAML-Konvertierung für KI-Sprachmodelle kopiert.', 'Kopierbutton enthält die passende Erfolgsmeldung.');
    check(str_contains($html, '<span class="admin-version">Version 1.0.0</span>'), 'Admin-Ansicht zeigt die Version aus der separaten Konfigurationsdatei.');
}
