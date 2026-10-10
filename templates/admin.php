<section class="admin-heading">
    <div>
        <p class="eyebrow">Küchenarchiv</p>
        <h1>Verwaltung</h1>
    </div>
    <span class="admin-count"><?= count($items) ?> <?= count($items) === 1 ? 'Rezept' : 'Rezepte' ?></span>
</section>

<?php if ($flash !== null): ?>
    <p class="notice <?= $flash['error'] ? 'notice-error' : 'notice-success' ?>" role="<?= $flash['error'] ? 'alert' : 'status' ?>"><?= escape($flash['message']) ?></p>
<?php endif; ?>

<section class="admin-section" aria-labelledby="upload-title">
    <h2 id="upload-title">YAML importieren</h2>
    <form class="upload-form" method="post" action="/admin/upload" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= escape($csrfToken) ?>">
        <label class="file-field" for="recipe-file">
            <span class="file-field-label">Rezeptdatei</span>
            <input id="recipe-file" type="file" name="recipe" accept=".yaml,.yml,application/yaml,text/yaml" required>
        </label>
        <button class="button button-primary" type="submit">Importieren</button>
    </form>
</section>

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