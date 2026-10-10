<section class="admin-heading">
    <div>
        <p class="eyebrow">Küchenarchiv</p>
        <h1>Verwaltung</h1>
    </div>
    <span class="admin-count"><?= count($items) ?> <?= count($items) === 1 ? 'Rezept' : 'Rezepte' ?></span>
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
        <?php if ($images !== []): ?>
            <h3 class="image-list-heading">Hochgeladene Bilder</h3>
            <ul class="image-list">
                <?php foreach ($images as $image): ?>
                    <li class="image-list-item">
                        <img src="<?= escape($image['url']) ?>" alt="" loading="lazy">
                        <input type="text" value="<?= escape($image['url']) ?>" readonly aria-label="Bild-URL">
                        <button class="button button-copy" type="button" data-copy="<?= escape($image['url']) ?>">Kopieren</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section class="admin-section" aria-labelledby="recipes-title">
    <h2 id="recipes-title">Rezepte</h2>
    <?php if ($items === []): ?>
        <p class="quiet-message">Noch keine Rezepte vorhanden.</p>
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
</section>