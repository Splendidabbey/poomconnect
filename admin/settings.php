<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$user = current_user();
$pageTitle = __('admin.settings_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_admin_action((int) $user['id'], 'settings_update', 'Admin settings page accessed');
    set_flash('success', __('flash.admin_settings_saved'));
    redirect(base_url('admin/settings.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header"><h1><?php _e('admin.settings_title'); ?></h1></div>
        <div class="card" style="max-width:560px;">
            <p style="margin-bottom:1.5rem;"><?php _e('admin.settings_placeholder'); ?></p>
            <form method="post">
                <div class="form-group">
                    <label><?php _e('app.name'); ?></label>
                    <input type="text" class="input" value="<?= e(app_name()) ?>" readonly>
                </div>
                <div class="form-group">
                    <label><?php _e('auth.email'); ?></label>
                    <input type="email" class="input" value="hello@poomconnect.com">
                </div>
                <button type="submit" class="btn btn-primary"><?php _e('admin.save'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
