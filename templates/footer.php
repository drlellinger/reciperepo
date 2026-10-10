</main>
<footer class="site-footer">
    <span><?= escape($appConfig['footer_name']) ?></span>
    <nav class="footer-links" aria-label="Rechtliche Informationen">
        <?php if (is_string($appConfig['privacy_url']) && $appConfig['privacy_url'] !== ''): ?>
            <a href="<?= escape($appConfig['privacy_url']) ?>">Datenschutzerklärung</a>
        <?php endif; ?>
        <?php if (is_string($appConfig['imprint_url']) && $appConfig['imprint_url'] !== ''): ?>
            <a href="<?= escape($appConfig['imprint_url']) ?>">Impressum</a>
        <?php endif; ?>
    </nav>
</footer>
<div class="toast" data-toast role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>