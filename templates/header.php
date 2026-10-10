<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= escape($title) ?> · <?= escape($appConfig['site_name']) ?></title>
    <meta name="description" content="<?= escape($description) ?>">
    <meta property="og:type" content="<?= escape($socialType) ?>">
    <meta property="og:site_name" content="<?= escape($appConfig['site_name']) ?>">
    <meta property="og:title" content="<?= escape($title) ?>">
    <meta property="og:description" content="<?= escape($description) ?>">
    <?php if ($pageUrl !== ''): ?>
        <meta property="og:url" content="<?= escape($pageUrl) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= isset($socialImage) && is_string($socialImage) && $socialImage !== '' ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= escape($title) ?>">
    <meta name="twitter:description" content="<?= escape($description) ?>">
    <?php if (isset($socialImage) && is_string($socialImage) && $socialImage !== ''): ?>
        <?php $socialImageUrl = absolutePublicUrl($socialImage, $siteOrigin); ?>
        <meta property="og:image" content="<?= escape($socialImageUrl) ?>">
        <meta property="og:image:alt" content="<?= escape($socialImageAlt ?? '') ?>">
        <meta name="twitter:image" content="<?= escape($socialImageUrl) ?>">
    <?php endif; ?>
    <?php
    $favicon = $appConfig['favicon'] ?? $appConfig['apple_touch_icon'] ?? $appConfig['logo_image'];
    $faviconUrl = is_string($favicon) && $favicon !== '' ? absolutePublicUrl($favicon, $siteOrigin) : null;
    $appleTouchIcon = $appConfig['apple_touch_icon'] ?? $appConfig['logo_image'] ?? $appConfig['favicon'];
    $appleTouchIconUrl = is_string($appleTouchIcon) && $appleTouchIcon !== '' ? absolutePublicUrl($appleTouchIcon, $siteOrigin) : null;
    ?>
    <?php if ($faviconUrl !== null): ?>
        <link rel="icon" href="<?= escape($faviconUrl) ?>">
    <?php endif; ?>
    <?php if ($appleTouchIconUrl !== null): ?>
        <link rel="apple-touch-icon" sizes="180x180" href="<?= escape($appleTouchIconUrl) ?>">
    <?php endif; ?>
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
        <a href="/" <?= ($path === '/' || str_starts_with($path, '/recipe/')) ? 'aria-current="page"' : '' ?>>Rezepte</a>
        <a href="/admin" <?= $isAdmin ? 'aria-current="page"' : '' ?>>Verwaltung</a>
    </nav>
</header>
<main id="main" class="page-shell" tabindex="-1">