<article class="recipe-page">
    <nav class="breadcrumbs" aria-label="Brotkrumennavigation">
        <a href="/">Rezepte</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= escape($item['recipe']['title']) ?></span>
    </nav>
    <header class="recipe-heading">
        <?php if (!empty($item['recipe']['tags'])): ?>
            <div class="recipe-tags" aria-label="Kategorien">
                <?php foreach ($item['recipe']['tags'] as $tag): ?>
                    <a class="tag-label" href="/?tag=<?= urlencode($tag) ?>"><?= escape($tag) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <h1><?= escape($item['recipe']['title']) ?></h1>
        <a class="download-link" href="/download?recipe=<?= escape($item['slug']) ?>" download>
            <span aria-hidden="true">↓</span> YAML herunterladen
        </a>
        <a class="download-link" href="/recipe/<?= escape($item['slug']) ?>/pdf" download>
            <span aria-hidden="true">↓</span> PDF herunterladen
        </a>
        <button class="download-link share-button" type="button" data-share data-share-title="<?= escape($item['recipe']['title']) ?>">
            <span aria-hidden="true">↗</span> Rezept teilen
        </button>
        <div class="share-fallback" data-share-fallback hidden>
            <label>
                <span>Rezeptlink</span>
                <input type="url" readonly data-share-url>
            </label>
        </div>
    </header>
    <div class="recipe-content">
        <?= $blocksHtml ?>
    </div>
</article>