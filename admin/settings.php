<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$user = current_user();
$pageTitle = 'Admin Settings';
$bodyClass = 'dashboard-page';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_admin_action((int) $user['id'], 'settings_update', 'Admin settings page accessed');
    set_flash('success', 'Settings saved.');
    redirect(base_url('admin/settings.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1>Settings</h1></div>
        <div class="card" style="max-width:560px;">
            <h3 style="margin-bottom:1rem;">Platform Settings</h3>
            <p style="margin-bottom:1.5rem;">Configure global platform settings. More options coming soon.</p>
            <form method="post">
                <div class="form-group">
                    <label>Platform Name</label>
                    <input type="text" class="input" value="Poom Connect" readonly>
                </div>
                <div class="form-group">
                    <label>Support Email</label>
                    <input type="email" class="input" value="hello@poomconnect.com">
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
