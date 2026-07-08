<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['id'] ?? 0);
$event = get_event_by_id($eventId);

if (!$event || !in_array($event['status'], ['published', 'live', 'completed'], true)) {
    set_flash('error', 'Event not found.');
    redirect(base_url('events.php'));
}

$pageTitle = $event['title'];
$cover = $event['cover_image'] ? upload_url($event['cover_image']) : default_event_image();

$countStmt = db()->prepare('SELECT COUNT(*) FROM event_participants WHERE event_id = ?');
$countStmt->execute([$eventId]);
$participantCount = (int) $countStmt->fetchColumn();
$spotsLeft = max(0, (int) $event['max_participants'] - $participantCount);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header" style="padding-bottom:0;">
    <div class="container">
        <span class="badge badge-purple"><?= e($event['organization_name']) ?></span>
        <h1 style="margin-top:1rem;"><?= e($event['title']) ?></h1>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container two-col">
        <div>
            <img src="<?= e($cover) ?>" alt="<?= e($event['title']) ?>" style="border-radius:var(--radius-lg);width:100%;height:320px;object-fit:cover;margin-bottom:1.5rem;">
            <div class="card">
                <h3 style="margin-bottom:1rem;">About This Event</h3>
                <p><?= nl2br(e($event['description'] ?? 'Join us for an unforgettable live matching experience.')) ?></p>
            </div>
        </div>
        <div class="card" style="position:sticky;top:100px;">
            <h3 style="margin-bottom:1.25rem;">Event Details</h3>
            <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
                <div><span style="color:var(--text-soft);font-size:0.85rem;">Date</span><br><strong><?= e(format_date($event['event_date'])) ?></strong></div>
                <div><span style="color:var(--text-soft);font-size:0.85rem;">Time</span><br><strong><?= e(format_time($event['start_time'])) ?> – <?= e(format_time($event['end_time'])) ?></strong></div>
                <div><span style="color:var(--text-soft);font-size:0.85rem;">Location</span><br><strong><?= e($event['location'] ?? 'TBA') ?></strong></div>
                <div><span style="color:var(--text-soft);font-size:0.85rem;">Ticket Price</span><br><strong class="gradient-text" style="font-size:1.5rem;"><?= e(format_currency((float) $event['ticket_price'])) ?></strong></div>
                <div><span style="color:var(--text-soft);font-size:0.85rem;">Availability</span><br>
                    <span class="badge badge-<?= $spotsLeft > 0 ? 'success' : 'danger' ?>"><?= $spotsLeft > 0 ? $spotsLeft . ' spots left' : 'Sold out' ?></span>
                </div>
            </div>
            <?php if ($spotsLeft > 0 && $event['status'] !== 'completed'): ?>
                <a href="<?= base_url('register.php?event_id=' . $eventId) ?>" class="btn btn-primary btn-block btn-lg">Register Now</a>
            <?php else: ?>
                <button class="btn btn-outline btn-block btn-lg" disabled>Registration Closed</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
