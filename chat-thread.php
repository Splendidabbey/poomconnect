<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_login(['participant', 'organizer', 'admin', 'super_admin']);

$userId = (int) current_user()['id'];
$roomId = (int) ($_GET['room'] ?? $_POST['room'] ?? 0);
$room = get_chat_room($roomId, $userId);

if (!$room) {
    set_flash('error', __('chat.not_found'));
    redirect(base_url('chat.php'));
}

$partnerId = (int) $room['user_a'] === $userId ? (int) $room['user_b'] : (int) $room['user_a'];
$partner = get_user_profile($partnerId);
$event = get_event_by_id((int) $room['event_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagePath = save_upload($_FILES['image'], 'chat', 'msg');
    }
    if ($body !== '' || $imagePath) {
        send_chat_message($roomId, $userId, $body, $imagePath);
    }
    redirect(base_url('chat-thread.php?room=' . $roomId));
}

$messages = get_chat_messages($roomId);
$pageTitle = __('chat.thread_with', ['name' => $partner['full_name'] ?? '']);

require_once APP_ROOT . '/includes/header.php';
?>

<section class="section content-section chat-thread-page">
    <div class="container chat-thread-layout">
        <div class="chat-thread-header card">
            <a href="<?= base_url('chat.php') ?>">← <?php _e('chat.back'); ?></a>
            <h1><?= e($partner['full_name'] ?? '') ?></h1>
            <span class="form-help"><?= e($event['title'] ?? '') ?></span>
        </div>

        <div class="chat-messages card" id="chat-messages">
            <?php foreach ($messages as $msg): ?>
                <div class="chat-message <?= (int) $msg['sender_id'] === $userId ? 'is-mine' : 'is-theirs' ?>">
                    <div class="chat-bubble">
                        <?php if ($msg['body']): ?><p><?= nl2br(e($msg['body'])) ?></p><?php endif; ?>
                        <?php if ($msg['image_path']): ?>
                            <img src="<?= e(upload_url($msg['image_path'])) ?>" alt="" class="chat-image">
                        <?php endif; ?>
                        <time><?= e(date('H:i', strtotime($msg['created_at']))) ?></time>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" enctype="multipart/form-data" class="card chat-compose">
            <input type="hidden" name="room" value="<?= $roomId ?>">
            <textarea name="body" class="textarea" rows="2" placeholder="<?= e(__('chat.placeholder')) ?>"></textarea>
            <div class="chat-compose-actions">
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="input">
                <button type="submit" class="btn btn-primary"><?php _e('chat.send'); ?></button>
            </div>
        </form>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
