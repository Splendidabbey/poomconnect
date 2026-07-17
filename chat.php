<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_login(['participant', 'organizer', 'admin', 'super_admin']);

$userId = (int) current_user()['id'];
$roomId = (int) ($_GET['room'] ?? 0);

if ($roomId) {
    redirect(base_url('chat-thread.php?room=' . $roomId));
}

$pageTitle = __('chat.title');
$rooms = get_user_chat_rooms($userId);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container"><h1><?php _e('chat.title'); ?></h1><p><?php _e('chat.subtitle'); ?></p></div>
</section>

<section class="section content-section">
    <div class="container">
        <?php if ($rooms): ?>
            <div class="chat-room-list">
                <?php foreach ($rooms as $room): ?>
                    <a href="<?= base_url('chat-thread.php?room=' . (int) $room['id']) ?>" class="card chat-room-item">
                        <strong><?= e($room['partner_name']) ?></strong>
                        <span><?= e($room['event_title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card">
                <p><?php _e('chat.no_chats'); ?></p>
                <p class="form-help"><?php _e('chat.unlock_hint'); ?></p>
            </div>
        <?php endif; ?>
        <p class="form-help future-note"><?php _e('chat.future_features'); ?></p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
