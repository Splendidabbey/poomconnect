<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['event_id'] ?? 0);
$userId = participant_session_user_id();
$token = trim($_GET['token'] ?? '');

if ($token !== '') {
    $ticketStmt = db()->prepare(
        'SELECT t.*, u.full_name, u.email, e.title AS event_title, e.event_date, e.start_time, e.location,
                ep.payment_status, ep.registration_status
         FROM tickets t
         JOIN users u ON u.id = t.user_id
         JOIN events e ON e.id = t.event_id
         JOIN event_participants ep ON ep.event_id = t.event_id AND ep.user_id = t.user_id
         WHERE t.qr_token = ? LIMIT 1'
    );
    $ticketStmt->execute([$token]);
    $ticket = $ticketStmt->fetch();
} elseif ($eventId && $userId) {
    $ticketStmt = db()->prepare(
        'SELECT t.*, u.full_name, u.email, e.title AS event_title, e.event_date, e.start_time, e.location,
                ep.payment_status, ep.registration_status
         FROM tickets t
         JOIN users u ON u.id = t.user_id
         JOIN events e ON e.id = t.event_id
         JOIN event_participants ep ON ep.event_id = t.event_id AND ep.user_id = t.user_id
         WHERE t.event_id = ? AND t.user_id = ? LIMIT 1'
    );
    $ticketStmt->execute([$eventId, $userId]);
    $ticket = $ticketStmt->fetch();
} else {
    $ticket = null;
}

if (!$ticket) {
    set_flash('error', __('flash.ticket_not_found'));
    redirect(base_url('events.php'));
}

$pageTitle = __('ticket_page.title');
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($ticket['qr_token']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="section content-section">
    <div class="container">
        <div class="ticket-page card">
            <span class="badge badge-purple"><?php _e('ticket_page.your_ticket'); ?></span>
            <h1><?= e($ticket['event_title']) ?></h1>
            <p><?= e($ticket['full_name']) ?></p>

            <div class="ticket-qr">
                <img src="<?= e($qrUrl) ?>" alt="QR Code Ticket">
            </div>

            <dl class="ticket-details">
                <div><dt><?php _e('ticket_page.ticket_id'); ?></dt><dd>#<?= (int) $ticket['id'] ?> · <?= e(substr($ticket['qr_token'], 0, 12)) ?>…</dd></div>
                <div><dt><?php _e('ticket_page.event'); ?></dt><dd><?= e($ticket['event_title']) ?></dd></div>
                <div><dt><?php _e('ticket_page.participant'); ?></dt><dd><?= e($ticket['full_name']) ?></dd></div>
                <div><dt><?php _e('common.date'); ?></dt><dd><?= e(format_date($ticket['event_date'])) ?> · <?= e(format_time($ticket['start_time'])) ?></dd></div>
                <div><dt><?php _e('common.location'); ?></dt><dd><?= e($ticket['location'] ?? __('common.tba')) ?></dd></div>
                <div><dt><?php _e('ticket_page.status'); ?></dt><dd><span class="badge badge-success"><?= e(status_label($ticket['payment_status'])) ?></span></dd></div>
                <div><dt><?php _e('ticket_page.checkin'); ?></dt><dd><span class="badge badge-<?= (int) $ticket['checked_in'] ? 'success' : 'purple' ?>"><?= (int) $ticket['checked_in'] ? e(__('ticket_page.checked_in')) : e(__('ticket_page.not_checked_in')) ?></span></dd></div>
            </dl>

            <p><?php _e('ticket_page.show_qr'); ?></p>
            <a href="<?= base_url('participant/live.php?event_id=' . (int) $ticket['event_id']) ?>" class="btn btn-primary"><?php _e('my_events.live'); ?></a>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
