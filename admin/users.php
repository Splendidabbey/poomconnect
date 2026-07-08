<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = 'Users';
$bodyClass = 'dashboard-page';

$users = db()->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1>Users</h1></div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= e($u['full_name']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="badge badge-purple"><?= e($u['role']) ?></span></td>
                                <td><?= e($u['phone'] ?? '—') ?></td>
                                <td><?= e(format_date($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
