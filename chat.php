<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_member();

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

<section class="dash-hero dash-hero-compact">
    <div class="container">
        <div class="dash-hero-copy">
            <h1><?php _e('chat.title'); ?></h1>
            <p><?php _e('chat.subtitle'); ?></p>
        </div>
    </div>
</section>

<section class="section content-section dash-section">
    <div class="container">
        <?php if ($rooms): ?>
            <div class="chat-room-list">
                <?php foreach ($rooms as $room): ?>
                    <a href="<?= base_url('chat-thread.php?room=' . (int) $room['id']) ?>" class="card chat-room-item">
                        <span class="chat-room-avatar" aria-hidden="true"><?= e(strtoupper(substr((string) $room['partner_name'], 0, 1))) ?></span>
                        <span class="chat-room-copy">
                            <strong><?= e($room['partner_name']) ?></strong>
                            <span><?= e($room['event_title']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dash-empty">
                <div class="dash-empty-orb" aria-hidden="true"></div>
                <h2><?php _e('chat.empty_title'); ?></h2>
                <p><?php _e('chat.empty_body'); ?></p>
                <div class="dash-empty-actions">
                    <a href="<?= base_url('events.php') ?>" class="btn btn-primary"><?php _e('chat.empty_cta'); ?></a>
                    <a href="<?= base_url('participant/matches.php') ?>" class="btn btn-outline"><?php _e('nav.matches'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
