<section class="empty-state error-page" aria-labelledby="error-title">
    <p class="eyebrow"><?= escape($errorCode) ?></p>
    <h1 id="error-title"><?= escape($errorTitle) ?></h1>
    <p><?= escape($errorMessage) ?></p>
    <a class="button button-primary" href="/">Zur Rezeptübersicht</a>
</section>