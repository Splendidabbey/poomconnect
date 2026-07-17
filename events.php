<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = __('events_page.title');

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'category_id' => (int) ($_GET['category_id'] ?? 0) ?: null,
    'city' => trim($_GET['city'] ?? ''),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'availability' => $_GET['availability'] ?? '',
    'event_type' => $_GET['event_type'] ?? '',
];

$events = search_events($filters, 50);
$categories = get_categories('event');
$cities = get_event_cities();
$pageMeta = page_meta([
    'title' => __('events_page.title'),
    'description' => __('events_page.subtitle'),
    'url' => base_url('events.php'),
]);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <span class="section-label"><?php _e('events_page.discover'); ?></span>
        <h1><?php _e('events_page.heading'); ?> <span class="gradient-text"><?php _e('events_page.heading_highlight'); ?></span></h1>
        <p><?php _e('events_page.subtitle'); ?></p>
    </div>
</section>

<section class="section content-section" style="padding-top:0;">
    <div class="container">
        <form method="get" class="filter-panel card" action="<?= base_url('events.php') ?>">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="q"><?php _e('events_page.search'); ?></label>
                    <input type="search" id="q" name="q" class="input" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('events_page.search_placeholder')) ?>">
                </div>
                <div class="form-group">
                    <label for="category_id"><?php _e('events_page.category'); ?></label>
                    <select id="category_id" name="category_id" class="select">
                        <option value=""><?php _e('event_form.all_categories'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="city"><?php _e('events_page.city'); ?></label>
                    <select id="city" name="city" class="select">
                        <option value=""><?php _e('events_page.all_cities'); ?></option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= e($city) ?>" <?= $filters['city'] === $city ? 'selected' : '' ?>><?= e($city) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="event_type"><?php _e('events_page.event_type'); ?></label>
                    <select id="event_type" name="event_type" class="select">
                        <option value=""><?php _e('events_page.all_types'); ?></option>
                        <?php foreach (event_types() as $type): ?>
                            <option value="<?= e($type) ?>" <?= $filters['event_type'] === $type ? 'selected' : '' ?>><?= e(event_type_label($type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_from"><?php _e('events_page.date_from'); ?></label>
                    <input type="date" id="date_from" name="date_from" class="input" value="<?= e($filters['date_from']) ?>">
                </div>
                <div class="form-group">
                    <label for="date_to"><?php _e('events_page.date_to'); ?></label>
                    <input type="date" id="date_to" name="date_to" class="input" value="<?= e($filters['date_to']) ?>">
                </div>
                <div class="form-group">
                    <label for="price_min"><?php _e('events_page.price_min'); ?></label>
                    <input type="number" id="price_min" name="price_min" class="input" min="0" value="<?= e((string) $filters['price_min']) ?>">
                </div>
                <div class="form-group">
                    <label for="price_max"><?php _e('events_page.price_max'); ?></label>
                    <input type="number" id="price_max" name="price_max" class="input" min="0" value="<?= e((string) $filters['price_max']) ?>">
                </div>
                <div class="form-group">
                    <label for="availability"><?php _e('events_page.availability'); ?></label>
                    <select id="availability" name="availability" class="select">
                        <option value=""><?php _e('events_page.all_availability'); ?></option>
                        <option value="available" <?= $filters['availability'] === 'available' ? 'selected' : '' ?>><?php _e('events_page.spots_available'); ?></option>
                        <option value="sold_out" <?= $filters['availability'] === 'sold_out' ? 'selected' : '' ?>><?php _e('events_page.sold_out'); ?></option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><?php _e('events_page.apply_filters'); ?></button>
                <a href="<?= base_url('events.php') ?>" class="btn btn-outline"><?php _e('events_page.clear_filters'); ?></a>
            </div>
        </form>

        <?php if ($events): ?>
            <p class="results-count"><?= e(__('events_page.results_count', ['count' => count($events)])) ?></p>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <?php
                    $cover = $event['cover_image'] ? upload_url($event['cover_image']) : default_event_image();
                    $spots = get_spots_left($event);
                    ?>
                    <article class="event-card card">
                        <a href="<?= e(event_url($event)) ?>" class="event-card-link">
                            <img src="<?= e($cover) ?>" alt="<?= e($event['title']) ?>" class="event-card-image">
                            <div class="event-card-body">
                                <div class="event-card-tags">
                                    <?php if (!empty($event['category_name'])): ?>
                                        <span class="badge badge-purple"><?= e($event['category_name']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge badge-outline"><?= e(event_type_label($event['event_type'] ?? 'social')) ?></span>
                                </div>
                                <h3 class="event-card-title"><?= e($event['title']) ?></h3>
                                <div class="event-card-meta">
                                    <span>📅 <?= e(format_date($event['event_date'])) ?> · <?= e(format_time($event['start_time'])) ?></span>
                                    <span>📍 <?= e($event['city'] ?: ($event['location'] ?? __('common.tba'))) ?></span>
                                    <span>🏢 <?= e($event['organization_name']) ?></span>
                                </div>
                                <div class="event-card-footer">
                                    <span class="badge badge-<?= $spots > 0 ? 'success' : 'danger' ?>"><?= e(spots_left_label($spots)) ?></span>
                                    <span class="event-card-price"><?= e(format_currency((float) $event['ticket_price'])) ?></span>
                                </div>
                            </div>
                        </a>
                        <a href="<?= e(event_url($event)) ?>" class="btn btn-primary btn-sm event-card-cta"><?php _e('events_page.view_event'); ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card">
                <h3><?php _e('events_page.no_events'); ?></h3>
                <p><?php _e('events_page.no_events_sub'); ?></p>
                <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary" style="margin-top:1rem;"><?php _e('nav.host_event'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
