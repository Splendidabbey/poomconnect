<?php

declare(strict_types=1);
?>
<a href="<?= base_url('notifications.php') ?>"
   class="nav-icon-btn<?= $currentPath === 'notifications.php' ? ' is-active' : '' ?>"
   aria-label="<?= e(__('nav.notifications')) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
        <path d="M10 19a2 2 0 0 0 4 0"/>
    </svg>
    <?php if ($navUnreadCount > 0): ?>
        <span class="nav-badge" aria-label="<?= e(__('notify.unread_count', ['count' => $navUnreadCount])) ?>"><?= $navUnreadCount > 99 ? '99+' : $navUnreadCount ?></span>
    <?php endif; ?>
</a>
