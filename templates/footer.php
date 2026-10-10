</main>
<footer class="site-footer">
    <span><?= \Recipe\FooterMarkdownRenderer::render((string) $appConfig['footer_name']) ?></span>
    <nav class="footer-links" aria-label="Rechtliche Informationen">
        <?php if (is_string($appConfig['privacy_url']) && $appConfig['privacy_url'] !== ''): ?>
            <?= \Recipe\FooterMarkdownRenderer::renderLinkSetting($appConfig['privacy_url'], 'Datenschutzerklärung') ?>
        <?php endif; ?>
        <?php if (is_string($appConfig['imprint_url']) && $appConfig['imprint_url'] !== ''): ?>
            <?= \Recipe\FooterMarkdownRenderer::renderLinkSetting($appConfig['imprint_url'], 'Impressum') ?>
        <?php endif; ?>
    </nav>
</footer>
<?php
$flashToast = \Recipe\Toast::message($flash ?? null);
$flashToastIsError = $flashToast !== null && !empty($flash['error']);
?>
<div class="toast<?= $flashToast !== null ? ' toast-visible' : '' ?><?= $flashToastIsError ? ' toast-error' : '' ?>" data-toast role="<?= $flashToastIsError ? 'alert' : 'status' ?>" aria-live="<?= $flashToastIsError ? 'assertive' : 'polite' ?>" aria-atomic="true" <?= $flashToast !== null ? 'data-flash-message="' . escape($flashToast) . '"' : '' ?>><?= $flashToast !== null ? escape($flashToast) : '' ?></div>
</body>
</html>