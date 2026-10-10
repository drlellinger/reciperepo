<section class="collection-heading">
    <div>
        <h1>Was kommt heute auf den Tisch?</h1>
        <p class="collection-count">
            <?= count($items) ?> von <?= $totalCount ?> <?= $totalCount === 1 ? 'Rezept' : 'Rezepten' ?>
        </p>
    </div>
</section>

<form class="collection-controls" method="get" action="/">
    <label>
        <span>Sortieren nach</span>
        <select name="sort">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Zuletzt geändert</option>
            <option value="category" <?= $sort === 'category' ? 'selected' : '' ?>>Kategorie</option>
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
    <button class="button button-filter" type="submit">Anwenden</button>
    <?php if ($sort !== 'name' || $selectedTag !== '' || $groupBy !== 'none'): ?>
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
            <?php if ($groupBy === 'tag'): ?>
                <h2 class="group-title"><?= $groupName === '' ? 'Ohne Kategorie' : escape($groupName) ?></h2>
            <?php endif; ?>
            <div class="recipe-list">
                <?php foreach ($groupItems as $item): ?>
                    <?php
                    $image = null;
                    foreach ($item['recipe']['blocks'] as $block) {
                        if ($block['type'] === 'image') {
                            $image = $block;
                            break;
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
                                <?php if (!empty($item['recipe']['tags'])): ?>
                                    <span class="recipe-tags">
                                        <?php foreach ($item['recipe']['tags'] as $tag): ?>
                                            <span class="tag-label"><?= escape($tag) ?></span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <span class="row-arrow" aria-hidden="true">↗</span>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>