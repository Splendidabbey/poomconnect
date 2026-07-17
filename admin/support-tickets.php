<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.support_tickets');
$tickets = get_support_tickets($_GET['status'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'], $_POST['status'])) {
    update_ticket_status((int) $_POST['ticket_id'], (string) $_POST['status'], (int) current_user()['id']);
    redirect(base_url('admin/support-tickets.php'));
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.support_tickets'); ?></h1>
        <?php foreach ($tickets as $t): ?>
            <div class="card" style="margin-bottom:1rem;">
                <strong><?= e($t['subject']) ?></strong> — <?= e($t['full_name']) ?> · <?= e(status_label($t['status'])) ?>
                <p><?= nl2br(e($t['body'])) ?></p>
                <form method="post" class="form-row">
                    <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                    <select name="status" class="select"><option value="in_progress"><?php _e('admin.in_progress'); ?></option><option value="resolved"><?php _e('admin.resolved'); ?></option><option value="closed"><?php _e('admin.closed'); ?></option></select>
                    <button class="btn btn-primary btn-sm"><?php _e('common.save'); ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
