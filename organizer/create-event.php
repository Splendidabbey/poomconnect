<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

if (!$org && !is_admin()) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('organizer.create_event_title');
$bodyClass = 'dashboard-page';
$errors = [];
$event = null;
if (!empty($_GET['template_id'])) {
    $tpl = get_event_template((int) $_GET['template_id']);
    if ($tpl) {
        $event = apply_template_to_event_data($tpl);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = parse_event_builder_data($_POST);

    if ($data['title'] === '') {
        $errors[] = __('validation.event_title_required');
    }
    if ($data['event_date'] === '') {
        $errors[] = __('validation.event_date_required');
    }
    if (!$org && !is_admin()) {
        $errors[] = __('validation.organization_required');
    }

    $coverPath = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $coverPath = save_upload($_FILES['cover_image'], 'events', 'event');
        if (!$coverPath) {
            $errors[] = __('validation.invalid_file');
        }
    }

    $ogImagePath = null;
    if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $ogImagePath = save_upload($_FILES['og_image'], 'events/og', 'og');
        if (!$ogImagePath) {
            $errors[] = __('validation.invalid_file');
        }
    }

    $bannerPath = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $bannerPath = save_upload($_FILES['banner_image'], 'events/banners', 'banner');
    }

    if ($errors === [] && $org) {
        if (!org_within_event_limit((int) $org['id'])) {
            $errors[] = __('subscription.event_limit');
        }
    }

    if ($errors === [] && $org) {
        $eventId = create_event_record_extended((int) $org['id'], (int) $user['id'], $data, $coverPath, $ogImagePath, $bannerPath);
        save_event_gallery($eventId, $_FILES['gallery_images'] ?? []);
        ensure_live_state($eventId, $data['round_duration']);

        set_flash('success', __('flash.event_created'));
        redirect(base_url('organizer/events.php'));
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1><?php _e('organizer.create_event_title'); ?></h1>
                <p><?php _e('organizer.event_details'); ?></p>
            </div>
        </div>

        <div class="card form-card-wide">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>

            <?php if (!$org && !is_admin()): ?>
                <div class="alert alert-error"><?php _e('flash.no_organization'); ?></div>
            <?php else: ?>
                <form method="post" enctype="multipart/form-data" data-loading>
                    <?php require APP_ROOT . '/includes/event-form-fields.php'; ?>
                    <button type="submit" class="btn btn-primary btn-lg"><?php _e('organizer.create_btn'); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
