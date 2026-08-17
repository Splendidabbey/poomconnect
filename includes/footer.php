</main>

<?php if (!isset($hideFooter) || !$hideFooter): ?>
<footer class="site-footer">
    <div class="footer-cta">
        <div class="container footer-cta-inner">
            <div>
                <h2><?php _e('footer.cta_title'); ?></h2>
                <p><?php _e('footer.cta_body'); ?></p>
            </div>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary"><?php _e('footer.cta_button'); ?></a>
        </div>
    </div>
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= base_url('index.php') ?>" class="logo footer-logo">
                <img src="<?= brand_logo('md') ?>" alt="<?= e(app_name()) ?>" class="logo-image logo-image-md">
            </a>
            <p class="footer-tagline"><?= e(app_tagline()) ?></p>
            <p class="footer-blurb"><?php _e('footer.blurb'); ?></p>
        </div>
        <nav class="footer-nav" aria-label="<?= e(__('nav.main_navigation')) ?>">
            <div class="footer-col">
                <h3><?php _e('footer.explore'); ?></h3>
                <a href="<?= base_url('events.php') ?>"><?php _e('nav.events'); ?></a>
                <a href="<?= base_url('blog.php') ?>"><?php _e('nav.blog'); ?></a>
                <a href="<?= base_url('index.php#how-it-works') ?>"><?php _e('nav.how_it_works'); ?></a>
                <a href="<?= base_url('index.php#pricing') ?>"><?php _e('nav.pricing'); ?></a>
            </div>
            <div class="footer-col">
                <h3><?php _e('footer.members'); ?></h3>
                <?php if (is_logged_in()): ?>
                    <a href="<?= base_url('my-events.php') ?>"><?php _e('nav.my_events'); ?></a>
                    <a href="<?= base_url('participant/matches.php') ?>"><?php _e('nav.matches'); ?></a>
                    <a href="<?= base_url('chat.php') ?>"><?php _e('nav.chat'); ?></a>
                    <a href="<?= base_url('organizer/dashboard.php') ?>"><?php _e('nav.host_studio'); ?></a>
                <?php else: ?>
                    <a href="<?= base_url('login.php') ?>"><?php _e('nav.login'); ?></a>
                    <a href="<?= base_url('signup.php') ?>"><?php _e('nav.signup'); ?></a>
                    <a href="<?= base_url('organizer/create-event.php') ?>"><?php _e('nav.host'); ?></a>
                <?php endif; ?>
            </div>
            <div class="footer-col">
                <h3><?php _e('footer.company'); ?></h3>
                <a href="<?= base_url('privacy.php') ?>"><?php _e('footer.privacy'); ?></a>
                <a href="<?= base_url('terms.php') ?>"><?php _e('footer.terms'); ?></a>
                <a href="mailto:hello@poomconnect.com"><?php _e('footer.contact'); ?></a>
            </div>
        </nav>
    </div>
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p><?= e(__('footer.copyright', ['year' => date('Y')])) ?></p>
            <a href="mailto:hello@poomconnect.com">hello@poomconnect.com</a>
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
