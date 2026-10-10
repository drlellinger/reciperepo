<section class="collection-heading">
    <div>
        <p class="eyebrow">Deine Rezeptsammlung</p>
        <h1>Was kommt heute auf den Tisch?</h1>
        <p class="collection-count"><?= count($items) ?> <?= count($items) === 1 ? 'Rezept' : 'Rezepte' ?></p>
    </div>
</section>

<?php if ($items === []): ?>
    <section class="empty-state" aria-labelledby="empty-title">
        <p class="empty-kicker" aria-hidden="true">K</p>
        <h2 id="empty-title">Dein Archiv beginnt hier.</h2>
    </section>
<?php else: ?>
    <section class="recipe-list" aria-label="Alle Rezepte">
        <?php foreach ($items as $item): ?>
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
                        <img class="recipe-thumb" src="<?= escape($image['url']) ?>" alt="<?= escape($image['alt']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="recipe-thumb recipe-thumb-empty" aria-hidden="true">K</span>
                    <?php endif; ?>
                    <span class="recipe-row-copy">
                        <span class="recipe-row-title"><?= escape($item['recipe']['title']) ?></span>
                        <span class="recipe-row-meta"><?= escape($item['recipe']['summary'] ?? 'Keine Kurzbeschreibung hinterlegt.') ?></span>
                    </span>
                    <span class="row-arrow" aria-hidden="true">↗</span>
                </a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>