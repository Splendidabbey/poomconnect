<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Events';
$events = get_published_events(50);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <span class="section-label">Discover</span>
        <h1>Upcoming <span class="gradient-text">Events</span></h1>
        <p>Find your next connection at a live matching event near you.</p>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container">
        <?php if ($events): ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <?php
                    $cover = $event['cover_image'] ? upload_url($event['cover_image']) : default_event_image();
                    $spots = get_spots_left($event);
                    ?>
                    <a href="<?= base_url('event.php?id=' . (int) $event['id']) ?>" class="event-card card">
                        <img src="<?= e($cover) ?>" alt="<?= e($event['title']) ?>" class="event-card-image">
                        <div class="event-card-body">
                            <h3 class="event-card-title"><?= e($event['title']) ?></h3>
                            <div class="event-card-meta">
                                <span>📅 <?= e(format_date($event['event_date'])) ?> · <?= e(format_time($event['start_time'])) ?></span>
                                <span>📍 <?= e($event['location'] ?? 'TBA') ?></span>
                                <span>🏢 <?= e($event['organization_name']) ?></span>
                            </div>
                            <div class="event-card-footer">
                                <span class="badge badge-<?= $spots > 0 ? 'success' : 'danger' ?>"><?= $spots > 0 ? $spots . ' spots left' : 'Sold out' ?></span>
                                <span><?= e(format_currency((float) $event['ticket_price'])) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card">
                <h3>No events available</h3>
                <p>Check back soon or host your own event.</p>
                <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary" style="margin-top:1rem;">Host Your Event</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
