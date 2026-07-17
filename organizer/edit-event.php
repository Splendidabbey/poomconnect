<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$eventId = (int) ($_GET['id'] ?? 0);
$event = get_event_by_id($eventId);

if (!$event || !user_can_manage_event((int) $user['id'], $event)) {
    set_flash('error', __('flash.event_not_found'));
    redirect(base_url('organizer/events.php'));
}

$pageTitle = __('organizer.edit_event_title');
$bodyClass = 'dashboard-page';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = parse_event_builder_data($_POST);
    $coverPath = $event['cover_image'];
    $ogImagePath = $event['og_image'] ?? null;
    $bannerPath = $event['banner_image'] ?? null;

    if ($data['title'] === '') {
        $errors[] = __('validation.event_title_required');
    }

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newCover = save_upload($_FILES['cover_image'], 'events', 'event');
        if ($newCover) {
            $coverPath = $newCover;
        } else {
            $errors[] = __('validation.invalid_file');
        }
    }

    if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newOg = save_upload($_FILES['og_image'], 'events/og', 'og');
        if ($newOg) {
            $ogImagePath = $newOg;
        } else {
            $errors[] = __('validation.invalid_file');
        }
    }

    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newBanner = save_upload($_FILES['banner_image'], 'events/banners', 'banner');
        if ($newBanner) {
            $bannerPath = $newBanner;
        }
    }

    if ($errors === []) {
        update_event_record_extended($eventId, $data, $coverPath, $ogImagePath, $bannerPath);
        save_event_gallery($eventId, $_FILES['gallery_images'] ?? []);

        set_flash('success', __('flash.event_updated'));
        redirect(base_url('organizer/events.php'));
    }

    $event = array_merge($event, $data);
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1><?php _e('organizer.edit_event_title'); ?></h1>
                <p><?= e($event['title']) ?></p>
            </div>
            <a href="<?= e(event_url($event)) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener"><?php _e('events_page.view_event'); ?></a>
        </div>

        <div class="card form-card-wide">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>

            <form method="post" enctype="multipart/form-data" data-loading>
                <?php require APP_ROOT . '/includes/event-form-fields.php'; ?>
                <button type="submit" class="btn btn-primary btn-lg"><?php _e('organizer.save_event'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
