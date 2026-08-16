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
        <nav class="footer-nav" aria-label="<?= e(__('nav.main_navigation')) ?>">
            <div class="footer-links">
                <a href="<?= base_url('events.php') ?>"><?php _e('nav.events'); ?></a>
                <a href="<?= base_url('blog.php') ?>"><?php _e('nav.blog'); ?></a>
                <a href="<?= base_url('signup.php') ?>"><?php _e('nav.signup'); ?></a>
                <a href="<?= base_url('organizer/create-event.php') ?>"><?php _e('nav.host_event'); ?></a>
            </div>
            <div class="footer-links">
                <a href="<?= base_url('privacy.php') ?>"><?php _e('footer.privacy'); ?></a>
                <a href="<?= base_url('terms.php') ?>"><?php _e('footer.terms'); ?></a>
                <a href="mailto:hello@poomconnect.com"><?php _e('footer.contact'); ?></a>
            </div>
        </nav>
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
window.PoomCsrfToken = <?= json_encode(csrf_token()) ?>;
</script>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
