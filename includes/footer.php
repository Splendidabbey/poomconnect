</main>

<?php if (!isset($hideFooter) || !$hideFooter): ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= base_url('index.php') ?>" class="logo footer-logo">
                <img src="<?= brand_logo('md') ?>" alt="<?= e(app_name()) ?>" class="logo-image logo-image-md">
            </a>
            <p class="footer-tagline"><?= e(app_tagline()) ?></p>
        </div>
        <div class="footer-links">
            <a href="#"><?php _e('footer.privacy'); ?></a>
            <a href="#"><?php _e('footer.terms'); ?></a>
            <a href="mailto:hello@poomconnect.com"><?php _e('footer.contact'); ?></a>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p><?= e(__('footer.copyright', ['year' => date('Y')])) ?></p>
        </div>
    </div>
</footer>
<?php endif; ?>

<script>
window.PoomI18n = <?= json_encode(js_translations(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
