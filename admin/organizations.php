<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = 'Organizations';
$bodyClass = 'dashboard-page';

$orgs = db()->query(
    'SELECT o.*, u.full_name AS owner_name FROM organizations o JOIN users u ON u.id = o.owner_id ORDER BY o.created_at DESC'
)->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1>Organizations</h1></div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Owner</th><th>PromptPay</th><th>Created</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orgs as $org): ?>
                            <tr>
                                <td><?= e($org['name']) ?></td>
                                <td><?= e($org['owner_name']) ?></td>
                                <td><?= e($org['promptpay_number'] ?? '—') ?></td>
                                <td><?= e(format_date($org['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
