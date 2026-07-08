<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = 'Payments';
$bodyClass = 'dashboard-page';

$payments = db()->query(
    'SELECT p.*, u.full_name, e.title AS event_title FROM payments p
     JOIN users u ON u.id = p.user_id
     JOIN events e ON e.id = p.event_id
     ORDER BY p.created_at DESC LIMIT 100'
)->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1>All Payments</h1></div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Participant</th><th>Event</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= e($p['full_name']) ?></td>
                                <td><?= e($p['event_title']) ?></td>
                                <td><?= e(format_currency((float) $p['amount'])) ?></td>
                                <td><span class="badge badge-<?= $p['payment_status'] === 'approved' ? 'success' : ($p['payment_status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e($p['payment_status']) ?></span></td>
                                <td><?= e(format_date($p['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
