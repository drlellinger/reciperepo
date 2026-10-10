<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= escape($title) ?> · <?= escape($appConfig['site_name']) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Zum Inhalt</a>
<header class="site-header">
    <a class="wordmark" href="/" aria-label="<?= escape($appConfig['site_name']) ?>, Übersicht">
        <?php if (is_string($appConfig['logo_image']) && $appConfig['logo_image'] !== ''): ?>
            <img class="wordmark-logo" src="<?= escape($appConfig['logo_image']) ?>" alt="<?= escape($appConfig['logo_alt']) ?>">
        <?php else: ?>
            <span class="wordmark-mark" aria-hidden="true"><?= escape($appConfig['logo_text']) ?></span>
        <?php endif; ?>
        <span><?= escape($appConfig['site_name']) ?></span>
    </a>
    <nav class="site-nav" aria-label="Hauptnavigation">
        <a href="/">Rezepte</a>
        <a href="/admin">Verwaltung</a>
    </nav>
</header>
<main id="main" class="page-shell">