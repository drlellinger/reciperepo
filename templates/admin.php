<section class="admin-heading">
    <div>
        <p class="eyebrow">Küchenarchiv</p>
        <h1>Verwaltung</h1>
    </div>
    <span class="admin-count"><?= $totalCount ?> <?= $totalCount === 1 ? 'Rezept' : 'Rezepte' ?></span>
</section>

<div class="upload-panels">
    <section class="admin-section upload-panel" aria-labelledby="recipe-upload-title">
        <h2 id="recipe-upload-title">Rezept-YAML</h2>
        <form class="upload-form" method="post" action="/admin/upload" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= escape($csrfToken) ?>">
            <label class="file-field" for="recipe-file">
                <span class="file-field-label">YAML-Dateien · jeweils maximal 1 MB</span>
                <input id="recipe-file" type="file" name="recipe[]" accept=".yaml,.yml,application/yaml,text/yaml" multiple required>
            </label>
            <button class="button button-primary" type="submit">Rezept importieren</button>
        </form>
    </section>

    <section class="admin-section upload-panel" aria-labelledby="image-upload-title">
        <h2 id="image-upload-title">Rezeptbilder</h2>
        <form class="upload-form" method="post" action="/admin/upload-image" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= escape($csrfToken) ?>">
            <label class="file-field" for="image-file">
                <span class="file-field-label">Bilder · JPEG, PNG, GIF oder WebP · jeweils maximal 8 MB</span>
                <input id="image-file" type="file" name="image[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple required>
            </label>
            <button class="button button-primary" type="submit">Bild hochladen</button>
        </form>
    </section>
</div>

<section class="admin-section" aria-labelledby="recipes-title">
    <h2 id="recipes-title">Rezepte</h2>
    <form class="collection-controls admin-controls" method="get" action="/admin">
        <label>
            <span>Rezepte suchen</span>
            <input type="search" name="q" value="<?= escape($search) ?>" placeholder="Name">
        </label>
        <label>
            <span>Sortieren nach</span>
            <select name="sort">
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Zuletzt geändert</option>
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
        <?php if ($search !== '' || $sort !== 'name' || $perPage !== 10): ?>
            <a class="reset-link" href="/admin">Zurücksetzen</a>
        <?php endif; ?>
    </form>
    <?php if ($totalCount === 0): ?>
        <p class="quiet-message"><?= $search === '' ? 'Noch keine Rezepte vorhanden.' : 'Keine Rezepte für diese Suche gefunden.' ?></p>
    <?php else: ?>
        <ul class="admin-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="/recipe/<?= escape($item['slug']) ?>"><?= escape($item['recipe']['title']) ?></a>
                    <form method="post" action="/admin/delete/<?= escape($item['slug']) ?>" data-confirm="Dieses Rezept löschen?">
                        <input type="hidden" name="_csrf" value="<?= escape($csrfToken) ?>">
                        <button class="button button-quiet-danger" type="submit" aria-label="<?= escape('Rezept ' . $item['recipe']['title'] . ' löschen') ?>">Löschen</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($pageCount > 1): ?>
        <nav class="pagination" aria-label="Seitennavigation">
            <?php if ($page > 1): ?>
                <a href="/admin?q=<?= urlencode($search) ?>&amp;sort=<?= urlencode($sort) ?>&amp;perPage=<?= $perPage ?>&amp;page=<?= $page - 1 ?>">Vorherige</a>
            <?php endif; ?>
            <span><?= $visibleStart ?>–<?= $visibleEnd ?> von <?= $totalCount ?></span>
            <?php if ($page < $pageCount): ?>
                <a href="/admin?q=<?= urlencode($search) ?>&amp;sort=<?= urlencode($sort) ?>&amp;perPage=<?= $perPage ?>&amp;page=<?= $page + 1 ?>">Nächste</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php if ($images !== []): ?>
    <section class="admin-section" aria-labelledby="image-list-title">
        <h2 id="image-list-title">Hochgeladene Bilder</h2>
        <ul class="image-list">
            <?php foreach ($images as $image): ?>
                <?php $imageUrl = absolutePublicUrl($image['url'], $siteOrigin); ?>
                <li class="image-list-item">
                    <img src="<?= escape($image['url']) ?>" alt="" loading="lazy">
                    <input type="text" value="<?= escape($imageUrl) ?>" readonly aria-label="Bild-URL">
                    <button class="button button-copy" type="button" data-copy="<?= escape($imageUrl) ?>">Kopieren</button>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>