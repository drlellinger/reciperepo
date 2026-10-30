<article class="recipe-page">
    <nav class="breadcrumbs" aria-label="Brotkrumennavigation">
        <a href="/">Rezepte</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= escape($item['recipe']['title']) ?></span>
    </nav>
    <header class="recipe-heading">
        <?php if (!empty($item['recipe']['tags']) || $totalDuration > 0 || $totalWaiting > 0): ?>
            <div class="recipe-heading-meta">
                <?php if (!empty($item['recipe']['tags'])): ?>
                    <div class="recipe-tags" aria-label="Kategorien">
                        <?php foreach ($item['recipe']['tags'] as $tag): ?>
                            <a class="tag-label" href="/?tag=<?= urlencode($tag) ?>"><?= escape($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($totalDuration > 0 || $totalWaiting > 0): ?>
                    <div class="recipe-times recipe-summary-times">
                        <?php if ($totalDuration > 0): ?>
                            <span class="recipe-duration" aria-label="Gesamtdauer">⏱ circa <?= escape(\Recipe\RecipeValidator::formatDuration($totalDuration)) ?> Gesamt</span>
                        <?php endif; ?>
                        <?php if ($totalWaiting > 0): ?>
                            <span class="recipe-waiting" aria-label="Gesamtwartezeit">Wartezeit: <?= escape(\Recipe\RecipeValidator::formatDuration($totalWaiting)) ?> Gesamt</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <h1><?= escape($item['recipe']['title']) ?></h1>
        <?php if (!empty($item['recipe']['aliases'])): ?>
            <p class="recipe-aliases"><span>Auch bekannt als:</span> <?= escape(implode(', ', $item['recipe']['aliases'])) ?></p>
        <?php endif; ?>
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