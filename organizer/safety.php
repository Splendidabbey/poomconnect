<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$org = get_organization_for_user((int) current_user()['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('safety.organizer_title');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_org_safe_badge((int) $org['id'], !empty($_POST['safe_badge']));
    set_flash('success', __('flash.settings_saved'));
    redirect(base_url('organizer/safety.php'));
}

$org = get_organization_by_id((int) $org['id']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('safety.organizer_title'); ?></h1></div>
        <div class="card" style="max-width:640px;">
            <form method="post">
                <label class="checkbox-label">
                    <input type="checkbox" name="safe_badge" value="1" <?= !empty($org['safe_event_badge']) ? 'checked' : '' ?>>
                    <?php _e('safety.enable_badge'); ?>
                </label>
                <p class="form-help"><?php _e('safety.badge_help'); ?></p>
                <button type="submit" class="btn btn-primary"><?php _e('organizer.save_settings'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
