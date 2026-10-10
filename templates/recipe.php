<article class="recipe-page">
    <nav class="breadcrumbs" aria-label="Brotkrumennavigation">
        <a href="/">Rezepte</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= escape($item['recipe']['title']) ?></span>
    </nav>
    <header class="recipe-heading">
        <p class="eyebrow">Aus dem Küchenarchiv</p>
        <h1><?= escape($item['recipe']['title']) ?></h1>
        <a class="download-link" href="/download?recipe=<?= escape($item['slug']) ?>" download>
            <span aria-hidden="true">↓</span> YAML herunterladen
        </a>
    </header>
    <div class="recipe-content">
        <?= $blocksHtml ?>
    </div>
</article>