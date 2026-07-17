<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$pageTitle = __('organizer.payments_title');
$bodyClass = 'dashboard-page';

$eventFilter = (int) ($_GET['event_id'] ?? 0);

if ($orgId) {
    $sql = "SELECT p.*, u.full_name, u.email, e.title AS event_title
            FROM payments p
            JOIN users u ON u.id = p.user_id
            JOIN events e ON e.id = p.event_id
            WHERE e.organization_id = ?";
    $params = [$orgId];

    if ($eventFilter) {
        $sql .= ' AND p.event_id = ?';
        $params[] = $eventFilter;
    }

    $sql .= ' ORDER BY p.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} else {
    $payments = [];
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1><?php _e('organizer.payments_title'); ?></h1>
            </div>
        </div>

        <div class="card">
            <?php if ($payments): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php _e('ticket_page.participant'); ?></th>
                                <th><?php _e('organizer.event'); ?></th>
                                <th><?php _e('common.amount'); ?></th>
                                <th>Slip</th>
                                <th><?php _e('common.status'); ?></th>
                                <th><?php _e('common.actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($payment['full_name']) ?></strong><br>
                                        <span style="color:var(--text-soft);font-size:0.8rem;"><?= e($payment['email']) ?></span>
                                    </td>
                                    <td><?= e($payment['event_title']) ?></td>
                                    <td><?= e(format_currency((float) $payment['amount'])) ?></td>
                                    <td>
                                        <?php if ($payment['slip_image']): ?>
                                            <a href="<?= e(upload_url($payment['slip_image'])) ?>" target="_blank">
                                                <img src="<?= e(upload_url($payment['slip_image'])) ?>" alt="Slip" class="slip-preview">
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = match ($payment['payment_status']) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'warning',
                                        };
                                        ?>
                                        <span class="badge badge-<?= $badge ?>"><?= e(status_label($payment['payment_status'])) ?></span>
                                    </td>
                                    <td class="table-actions">
                                        <?php if ($payment['payment_status'] === 'pending' && $payment['slip_image']): ?>
                                            <button class="btn btn-primary btn-sm" data-confirm="<?= e(__('js.approve_payment')) ?>" onclick="approvePayment(<?= (int) $payment['id'] ?>)"><?php _e('organizer.approve'); ?></button>
                                            <button class="btn btn-outline btn-sm" data-confirm="<?= e(__('js.reject_payment')) ?>" onclick="rejectPayment(<?= (int) $payment['id'] ?>)"><?php _e('organizer.reject'); ?></button>
                                        <?php elseif ($payment['payment_status'] === 'approved'): ?>
                                            <span class="badge badge-success"><?php _e('status.issued'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p><?php _e('organizer.no_payments'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function approvePayment(id) {
    const res = await apiPost('<?= base_url('api/approve-payment.php') ?>', { payment_id: id });
    alert(res.message);
    if (res.success) location.reload();
}
async function rejectPayment(id) {
    const res = await apiPost('<?= base_url('api/reject-payment.php') ?>', { payment_id: id });
    alert(res.message);
    if (res.success) location.reload();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
