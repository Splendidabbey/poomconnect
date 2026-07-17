<?php

declare(strict_types=1);
?>
<?php if (is_logged_in()): ?>
    <?php if (is_admin()): ?>
        <a href="<?= base_url('admin/dashboard.php') ?>" class="nav-login"><?php _e('nav.dashboard'); ?></a>
    <?php elseif (is_organizer()): ?>
        <a href="<?= base_url('organizer/dashboard.php') ?>" class="nav-login"><?php _e('nav.dashboard'); ?></a>
    <?php else: ?>
        <a href="<?= base_url('my-events.php') ?>" class="nav-login"><?php _e('nav.my_events'); ?></a>
        <a href="<?= base_url('notifications.php') ?>" class="nav-login nav-link-badge">
            <?php _e('nav.notifications'); ?>
            <?php if ($navUnreadCount > 0): ?>
                <span class="nav-badge" aria-label="<?= e(__('notify.unread_count', ['count' => $navUnreadCount])) ?>"><?= $navUnreadCount > 99 ? '99+' : $navUnreadCount ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
    <a href="<?= base_url('logout.php') ?>" class="nav-login"><?php _e('nav.logout'); ?></a>
<?php else: ?>
    <a href="<?= base_url('login.php') ?>" class="nav-login"><?php _e('nav.login'); ?></a>
    <a href="<?= base_url('signup.php') ?>" class="nav-signup"><?php _e('nav.signup'); ?></a>
<?php endif; ?>
