<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = trim($_GET['slug'] ?? '');
$eventId = (int) ($_GET['id'] ?? 0);

if ($slug !== '') {
    $event = get_event_by_slug($slug);
} else {
    $event = get_event_by_id($eventId);
}

if (!$event || !in_array($event['status'], ['published', 'live', 'completed'], true)) {
    set_flash('error', __('flash.event_not_found'));
    redirect(base_url('events.php'));
}

$eventId = (int) $event['id'];
$pageTitle = $event['title'];
$pageMeta = event_page_meta($event);

$cover = $event['cover_image'] ? upload_url($event['cover_image']) : default_event_image();
$gallery = get_event_images($eventId);
$participantCount = (int) ($event['participant_count'] ?? 0);
$spotsLeft = max(0, (int) $event['max_participants'] - $participantCount);
$mapUrl = google_maps_embed_url($event);
$shareUrl = event_url($event);
$orgLogo = !empty($event['organization_logo']) ? upload_url($event['organization_logo']) : default_avatar($event['organization_name']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header event-detail-header">
    <div class="container">
        <div class="event-detail-badges">
            <?php if (!empty($event['category_name'])): ?>
                <span class="badge badge-purple"><?= e($event['category_name']) ?></span>
            <?php endif; ?>
            <span class="badge badge-outline"><?= e(event_type_label($event['event_type'] ?? 'social')) ?></span>
        </div>
        <h1><?= e($event['title']) ?></h1>
    </div>
</section>

<section class="section content-section event-detail-section">
    <div class="container two-col event-detail-grid">
        <div class="event-detail-main">
            <div class="event-gallery">
                <img src="<?= e($cover) ?>" alt="<?= e($event['title']) ?>" class="event-gallery-cover">
                <?php if ($gallery): ?>
                    <div class="event-gallery-thumbs">
                        <?php foreach ($gallery as $img): ?>
                            <img src="<?= e(upload_url($img['image_path'])) ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><?php _e('event_page.about'); ?></h2>
                <p class="event-description"><?= nl2br(e($event['description'] ?? __('common.default_event_description'))) ?></p>
            </div>

            <?php if ($mapUrl): ?>
            <div class="card">
                <h2><?php _e('event_page.location_map'); ?></h2>
                <p class="event-location-text"><?= e($event['location'] ?? '') ?><?= !empty($event['city']) ? ', ' . e($event['city']) : '' ?></p>
                <div class="map-embed">
                    <iframe src="<?= e($mapUrl) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="<?= e($event['title']) ?>"></iframe>
                </div>
            </div>
            <?php endif; ?>

            <div class="card organizer-card">
                <h2><?php _e('event_page.organizer'); ?></h2>
                <div class="organizer-profile">
                    <img src="<?= e($orgLogo) ?>" alt="<?= e($event['organization_name']) ?>" class="organizer-avatar">
                    <div>
                        <strong><?= e($event['organization_name']) ?></strong>
                        <?php if (!empty($event['city'])): ?>
                            <p><?= e($event['city']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <aside class="card event-detail-sidebar">
            <h2><?php _e('event_page.details'); ?></h2>
            <dl class="detail-list">
                <div>
                    <dt><?php _e('common.date'); ?></dt>
                    <dd><?= e(format_date($event['event_date'])) ?></dd>
                </div>
                <div>
                    <dt><?php _e('common.time'); ?></dt>
                    <dd><?= e(format_time($event['start_time'])) ?> – <?= e(format_time($event['end_time'])) ?></dd>
                </div>
                <div>
                    <dt><?php _e('common.location'); ?></dt>
                    <dd><?= e($event['location'] ?? __('common.tba')) ?><?= !empty($event['city']) ? ', ' . e($event['city']) : '' ?></dd>
                </div>
                <div>
                    <dt><?php _e('event_page.ticket_price'); ?></dt>
                    <dd class="detail-price"><?= e(format_currency((float) $event['ticket_price'])) ?></dd>
                </div>
                <div>
                    <dt><?php _e('event_page.remaining_seats'); ?></dt>
                    <dd><span class="badge badge-<?= $spotsLeft > 0 ? 'success' : 'danger' ?>"><?= e(spots_left_label($spotsLeft)) ?></span></dd>
                </div>
            </dl>

            <div class="event-detail-actions">
                <?php if ($spotsLeft > 0 && $event['status'] !== 'completed'): ?>
                    <a href="<?= base_url('register.php?event_id=' . $eventId) ?>" class="btn btn-primary btn-block btn-lg"><?php _e('event_page.join_event'); ?></a>
                <?php else: ?>
                    <button class="btn btn-outline btn-block btn-lg" disabled><?php _e('common.registration_closed_btn'); ?></button>
                <?php endif; ?>
                <div class="event-share-wrap">
                    <?= render_social_share([
                        'url' => $shareUrl,
                        'title' => $event['title'],
                        'entity_type' => 'event',
                        'entity_id' => $eventId,
                    ]) ?>
                </div>
            </div>
        </aside>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
