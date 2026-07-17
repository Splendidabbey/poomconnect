<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.marketplace');
$applications = pending_host_applications();
$organizers = marketplace_organizers(20);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    review_host_application((int) $_POST['app_id'], (string) $_POST['status'], (int) current_user()['id'], trim($_POST['notes'] ?? ''));
    redirect(base_url('admin/marketplace.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feature_org'])) {
    set_org_featured((int) $_POST['feature_org'], !empty($_POST['featured']));
    redirect(base_url('admin/marketplace.php'));
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.marketplace'); ?></h1>
        <h2><?php _e('marketplace.host_applications'); ?></h2>
        <?php foreach ($applications as $a): ?>
            <div class="card" style="margin-bottom:1rem;">
                <strong><?= e($a['full_name']) ?></strong> — <?= e($a['organization_name']) ?>
                <p><?= e($a['experience'] ?? '') ?></p>
                <form method="post" class="form-row">
                    <input type="hidden" name="app_id" value="<?= (int) $a['id'] ?>">
                    <select name="status" class="select"><option value="approved"><?php _e('admin.approve'); ?></option><option value="rejected"><?php _e('admin.reject'); ?></option></select>
                    <input class="input" name="notes" placeholder="<?= e(__('admin.notes')) ?>">
                    <button class="btn btn-primary btn-sm"><?php _e('common.save'); ?></button>
                </form>
            </div>
        <?php endforeach; ?>
        <h2 style="margin-top:2rem;"><?php _e('marketplace.featured'); ?></h2>
        <?php foreach ($organizers as $o): ?>
            <form method="post" class="card form-row" style="margin-bottom:0.5rem;">
                <input type="hidden" name="feature_org" value="<?= (int) $o['id'] ?>">
                <span style="flex:1;"><?= e($o['name']) ?> ⭐ <?= e(number_format((float) $o['rating_avg'], 1)) ?></span>
                <label class="checkbox-label"><input type="checkbox" name="featured" value="1" <?= !empty($o['is_featured']) ? 'checked' : '' ?>> <?php _e('marketplace.featured'); ?></label>
                <button class="btn btn-outline btn-sm"><?php _e('common.save'); ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
