<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$user = current_user();
$stats = admin_stats();

$pageTitle = 'Admin Dashboard';
$bodyClass = 'dashboard-page';

$recentLogs = db()->query(
    'SELECT al.*, u.full_name FROM admin_logs al JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 10'
)->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Platform overview — <?= e($user['full_name']) ?></p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card card">
                <div class="stat-card-label">Total Users</div>
                <div class="stat-card-value"><?= $stats['users'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Organizations</div>
                <div class="stat-card-value"><?= $stats['organizations'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Events</div>
                <div class="stat-card-value"><?= $stats['events'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Pending Payments</div>
                <div class="stat-card-value gradient-text"><?= $stats['payments_pending'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Total Matches</div>
                <div class="stat-card-value"><?= $stats['matches'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Total Revenue</div>
                <div class="stat-card-value" style="font-size:1.5rem;"><?= e(format_currency($stats['revenue'])) ?></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:1rem;">Recent Activity</h3>
            <?php if ($recentLogs): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>User</th><th>Action</th><th>Details</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td><?= e($log['full_name']) ?></td>
                                    <td><?= e($log['action']) ?></td>
                                    <td><?= e($log['details'] ?? '') ?></td>
                                    <td><?= e(date('M j, Y g:i A', strtotime($log['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><p>No activity logs yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
