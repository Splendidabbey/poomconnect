<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_login(['participant', 'organizer', 'admin', 'super_admin']);

$userId = (int) current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    mark_notifications_read($userId);
    redirect(base_url('notifications.php'));
}

$pageTitle = __('notify.title');
$notifications = get_user_notifications($userId);
mark_notifications_read($userId);

require_once APP_ROOT . '/includes/header.php';
?>

<section class="page-header">
    <div class="container"><h1><?php _e('notify.title'); ?></h1></div>
</section>

<section class="section content-section">
    <div class="container">
        <div class="notify-channels card">
            <p><?php _e('notify.channels'); ?>: <?php _e('notify.email'); ?> · <?php _e('notify.line'); ?> · <?php _e('notify.push'); ?> · <?php _e('notify.sms_future'); ?></p>
        </div>
        <?php if ($notifications): ?>
            <div class="notification-list">
                <?php foreach ($notifications as $n): ?>
                    <div class="card notification-item">
                        <strong><?= e($n['title']) ?></strong>
                        <p><?= e($n['body']) ?></p>
                        <time><?= e(format_date($n['created_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card"><p><?php _e('notify.empty'); ?></p></div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
