<?php

declare(strict_types=1);

$navAccountMode ??= 'dropdown';
?>
<?php if (is_admin()): ?>
    <a href="<?= base_url('admin/dashboard.php') ?>" class="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? 'active' : '' ?>"><?php _e('nav.admin'); ?></a>
<?php endif; ?>
<a href="<?= base_url('profile.php') ?>" class="<?= $currentPath === 'profile.php' ? 'active' : '' ?>"><?php _e('nav.profile'); ?></a>
<?php if ($navAccountMode === 'list'): ?>
    <a href="<?= base_url('notifications.php') ?>" class="nav-link-badge<?= $currentPath === 'notifications.php' ? ' active' : '' ?>">
        <?php _e('nav.notifications'); ?>
        <?php if ($navUnreadCount > 0): ?>
            <span class="nav-badge" aria-label="<?= e(__('notify.unread_count', ['count' => $navUnreadCount])) ?>"><?= $navUnreadCount > 99 ? '99+' : $navUnreadCount ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>
<a href="<?= base_url('organizer/dashboard.php') ?>" class="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/organizer/') ? 'active' : '' ?>"><?php _e('nav.host_studio'); ?></a>
<a href="<?= base_url('community/groups.php') ?>" class="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/community/') ? 'active' : '' ?>"><?php _e('sidebar.community'); ?></a>
<a href="<?= base_url('loyalty.php') ?>" class="<?= $currentPath === 'loyalty.php' ? 'active' : '' ?>"><?php _e('nav.loyalty'); ?></a>
<a href="<?= base_url('logout.php') ?>"><?php _e('nav.logout'); ?></a>
