<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_moderator();

$pageTitle = __('admin.moderation');
$reports = pending_reports();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['status'])) {
    update_report_status((int) $_POST['report_id'], (string) $_POST['status'], (int) current_user()['id']);
    if ($_POST['status'] === 'actioned' && !empty($_POST['verify_user'])) {
        verify_user((int) $_POST['reported_id']);
    }
    set_flash('success', __('admin.report_updated'));
    redirect(base_url('admin/moderation.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.moderation'); ?></h1>
        <?php if ($reports): ?>
            <?php foreach ($reports as $r): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <p><strong><?= e($r['reporter_name']) ?></strong> → <?= e($r['reported_name']) ?></p>
                    <p><?= e(__('safety.reason_' . $r['reason'])) ?> — <?= e($r['details'] ?? '') ?></p>
                    <form method="post" class="form-row">
                        <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
                        <input type="hidden" name="reported_id" value="<?= (int) $r['reported_id'] ?>">
                        <select name="status" class="select"><option value="reviewed"><?php _e('admin.reviewed'); ?></option><option value="dismissed"><?php _e('admin.dismissed'); ?></option><option value="actioned"><?php _e('admin.actioned'); ?></option></select>
                        <label class="checkbox-label"><input type="checkbox" name="verify_user" value="1"> <?php _e('safety.verify_reported'); ?></label>
                        <button class="btn btn-primary btn-sm"><?php _e('common.save'); ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state card"><p><?php _e('admin.no_reports'); ?></p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
