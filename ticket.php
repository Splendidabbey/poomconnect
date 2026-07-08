<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['event_id'] ?? 0);
$userId = (int) ($_SESSION['participant_user_id'] ?? 0);
$token = trim($_GET['token'] ?? '');

if ($token !== '') {
    $ticketStmt = db()->prepare(
        'SELECT t.*, u.full_name, e.title AS event_title, e.event_date, e.start_time, e.location,
                ep.payment_status
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
        'SELECT t.*, u.full_name, e.title AS event_title, e.event_date, e.start_time, e.location,
                ep.payment_status
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
    set_flash('error', 'Ticket not found. Payment may still be pending.');
    redirect(base_url('events.php'));
}

$pageTitle = 'Your Ticket';
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($ticket['qr_token']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="section">
    <div class="container">
        <div class="ticket-page card">
            <span class="badge badge-purple">Digital Ticket</span>
            <h1 style="margin:1rem 0 0.5rem;font-size:1.5rem;"><?= e($ticket['event_title']) ?></h1>
            <p><?= e($ticket['full_name']) ?></p>

            <div class="ticket-qr">
                <img src="<?= e($qrUrl) ?>" alt="QR Code Ticket">
            </div>

            <p style="font-size:0.85rem;color:var(--text-soft);margin-bottom:1.5rem;">Token: <?= e($ticket['qr_token']) ?></p>

            <div style="display:grid;gap:0.75rem;text-align:left;margin-bottom:1.5rem;">
                <div><strong>Date:</strong> <?= e(format_date($ticket['event_date'])) ?> · <?= e(format_time($ticket['start_time'])) ?></div>
                <div><strong>Location:</strong> <?= e($ticket['location'] ?? 'TBA') ?></div>
                <div><strong>Payment:</strong>
                    <span class="badge badge-<?= $ticket['payment_status'] === 'approved' ? 'success' : 'warning' ?>">
                        <?= e(ucfirst($ticket['payment_status'])) ?>
                    </span>
                </div>
                <div><strong>Check-in:</strong>
                    <span class="badge badge-<?= (int) $ticket['checked_in'] ? 'success' : 'purple' ?>">
                        <?= (int) $ticket['checked_in'] ? 'Checked In' : 'Not Yet' ?>
                    </span>
                </div>
            </div>

            <p style="font-size:0.9rem;">Show this QR code at the event entrance for check-in.</p>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
