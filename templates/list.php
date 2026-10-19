<section class="collection-heading">
    <div>
        <h1>Was kommt heute auf den Tisch?</h1>
        <p class="collection-count">
            <?php if ($totalCount === 0): ?>
                0 Rezepte
            <?php elseif ($totalCount <= $perPage): ?>
                <?= $totalCount ?> <?= $totalCount === 1 ? 'Rezept' : 'Rezepte' ?>
            <?php else: ?>
                <?= $visibleStart ?>–<?= $visibleEnd ?> von <?= $totalCount ?> Rezepten
            <?php endif; ?>
        </p>
    </div>
</section>

<form class="collection-controls" method="get" action="/">
    <input type="hidden" name="page" value="1">
    <label>
        <span>Sortieren nach</span>
        <select name="sort">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Zuletzt geändert</option>
            <option value="category" <?= $sort === 'category' ? 'selected' : '' ?>>Kategorie</option>
            <option value="duration" <?= $sort === 'duration' ? 'selected' : '' ?>>Dauer</option>
        </select>
    </label>
    <label>
        <span>Kategorie filtern</span>
        <select name="tag">
            <option value="">Alle Kategorien</option>
            <?php foreach ($tags as $tag): ?>
                <option value="<?= escape($tag) ?>" <?= $selectedTag === $tag ? 'selected' : '' ?>><?= escape($tag) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>Gruppieren</span>
        <select name="group">
            <option value="none" <?= $groupBy === 'none' ? 'selected' : '' ?>>Keine Gruppierung</option>
            <option value="tag" <?= $groupBy === 'tag' ? 'selected' : '' ?>>Nach Kategorie</option>
        </select>
    </label>
    <label>
        <span>Rezepte pro Seite</span>
        <select name="perPage">
            <?php foreach ([5, 10, 20, 50] as $choice): ?>
                <option value="<?= $choice ?>" <?= $perPage === $choice ? 'selected' : '' ?>><?= $choice ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button button-filter" type="submit">Anwenden</button>
    <?php if ($sort !== 'name' || $selectedTag !== '' || $groupBy !== 'none' || $perPage !== 10): ?>
        <a class="reset-link" href="/">Zurücksetzen</a>
    <?php endif; ?>
</form>

<?php if ($items === []): ?>
    <section class="empty-state" aria-labelledby="empty-title">
        <p class="empty-kicker" aria-hidden="true">K</p>
        <h2 id="empty-title"><?= $totalCount === 0 ? 'Dein Archiv beginnt hier.' : 'Keine Rezepte für diesen Filter.' ?></h2>
    </section>
<?php else: ?>
    <?php foreach ($groups as $groupName => $groupItems): ?>
        <section class="recipe-group" aria-label="<?= $groupName === '' ? 'Rezeptliste' : 'Kategorie ' . escape($groupName) ?>">
            <?php if ($groupBy === 'tag' && ($groupName !== '' || $selectedTag === '')): ?>
                <h2 class="group-title"><?= $groupName === '' ? 'Ohne Kategorie' : escape($groupName) ?></h2>
            <?php endif; ?>
            <div class="recipe-list">
                <?php foreach ($groupItems as $item): ?>
                    <?php
                    $image = $item['recipe']['image'] ?? null;
                    if ($image === null) {
                        foreach ($item['recipe']['blocks'] as $block) {
                            if ($block['type'] === 'image') {
                                $image = $block;
                                break;
                            }
                        }
                    }
                    ?>
                    <article class="recipe-row">
                        <a class="recipe-row-link" href="/recipe/<?= escape($item['slug']) ?>">
                            <?php if ($image !== null): ?>
                                <img class="recipe-thumb" src="<?= escape($image['url']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="recipe-thumb recipe-thumb-empty" aria-hidden="true">K</span>
                            <?php endif; ?>
                            <span class="recipe-row-copy">
                                <span class="recipe-row-title"><?= escape($item['recipe']['title']) ?></span>
                                <span class="recipe-row-meta"><?= escape($item['recipe']['summary'] ?? 'Keine Kurzbeschreibung hinterlegt.') ?></span>
                            </span>
                            <span class="row-arrow" aria-hidden="true">↗</span>
                        </a>
                        <?php $duration = \Recipe\RecipeValidator::totalDuration($item['recipe']); ?>
                        <?php if (!empty($item['recipe']['tags']) || $duration > 0): ?>
                            <div class="recipe-row-meta-line">
                                <?php if (!empty($item['recipe']['tags'])): ?>
                                    <span class="recipe-tags recipe-row-tags">
                                        <?php foreach ($item['recipe']['tags'] as $tag): ?>
                                            <a class="tag-label" href="/?sort=<?= urlencode($sort) ?>&amp;tag=<?= urlencode($tag) ?>&amp;group=<?= urlencode($groupBy) ?>&amp;perPage=<?= $perPage ?>&amp;page=1"<?= $selectedTag === $tag ? ' aria-current="true"' : '' ?>><?= escape($tag) ?></a>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($duration > 0): ?>
                                    <span class="recipe-row-duration">⏱ circa <?= escape(\Recipe\RecipeValidator::formatDuration($duration)) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($pageCount > 1): ?>
        <nav class="pagination" aria-label="Seitennavigation">
            <?php if ($page > 1): ?>
                <a href="/?sort=<?= urlencode($sort) ?>&amp;tag=<?= urlencode($selectedTag) ?>&amp;group=<?= urlencode($groupBy) ?>&amp;perPage=<?= $perPage ?>&amp;page=<?= $page - 1 ?>">Vorherige</a>
            <?php endif; ?>
            <span>Seite <?= $page ?> von <?= $pageCount ?></span>
            <?php if ($page < $pageCount): ?>
                <a href="/?sort=<?= urlencode($sort) ?>&amp;tag=<?= urlencode($selectedTag) ?>&amp;group=<?= urlencode($groupBy) ?>&amp;perPage=<?= $perPage ?>&amp;page=<?= $page + 1 ?>">Nächste</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>