<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$pageTitle = 'Payment Approvals';
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
                <h1>Payment Approvals</h1>
                <p>Review and approve participant payment slips</p>
            </div>
        </div>

        <div class="card">
            <?php if ($payments): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Event</th>
                                <th>Amount</th>
                                <th>Slip</th>
                                <th>Status</th>
                                <th>Actions</th>
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
                                        <span class="badge badge-<?= $badge ?>"><?= e(ucfirst($payment['payment_status'])) ?></span>
                                    </td>
                                    <td class="table-actions">
                                        <?php if ($payment['payment_status'] === 'pending' && $payment['slip_image']): ?>
                                            <button class="btn btn-primary btn-sm" onclick="approvePayment(<?= (int) $payment['id'] ?>)">Approve</button>
                                            <button class="btn btn-outline btn-sm" onclick="rejectPayment(<?= (int) $payment['id'] ?>)">Reject</button>
                                        <?php elseif ($payment['payment_status'] === 'approved'): ?>
                                            <span class="badge badge-success">Ticket Issued</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No payments yet</h3>
                    <p>Payment slips will appear here when participants register.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function approvePayment(id) {
    if (!confirm('Approve this payment and generate ticket?')) return;
    const res = await apiPost('<?= base_url('api/approve-payment.php') ?>', { payment_id: id });
    alert(res.message);
    if (res.success) location.reload();
}
async function rejectPayment(id) {
    if (!confirm('Reject this payment?')) return;
    const res = await apiPost('<?= base_url('api/reject-payment.php') ?>', { payment_id: id });
    alert(res.message);
    if (res.success) location.reload();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
