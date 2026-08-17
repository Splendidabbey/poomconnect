<?php

declare(strict_types=1);
?>
<?php if (is_logged_in()): ?>
    <?php if (($navAccountMode ?? 'dropdown') === 'list'): ?>
        <?php require APP_ROOT . '/includes/nav-account-links.php'; ?>
    <?php else: ?>
        <?php require APP_ROOT . '/includes/nav-notify.php'; ?>
        <?php require APP_ROOT . '/includes/nav-account.php'; ?>
    <?php endif; ?>
<?php else: ?>
    <a href="<?= base_url('login.php') ?>" class="nav-login"><?php _e('nav.login'); ?></a>
    <a href="<?= base_url('signup.php') ?>" class="nav-signup"><?php _e('nav.signup'); ?></a>
<?php endif; ?>
