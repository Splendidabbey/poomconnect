</main>

<?php if (!isset($hideFooter) || !$hideFooter): ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= base_url('index.php') ?>" class="logo footer-logo">
                <img src="<?= brand_logo('md') ?>" alt="Poom Connect" class="logo-image logo-image-md">
            </a>
            <p class="footer-tagline"><?= e(APP_TAGLINE) ?></p>
        </div>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms</a>
            <a href="mailto:hello@poomconnect.com">Contact</a>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Poom Connect. All rights reserved.</p>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
