<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.reports');
$revenue = platform_revenue_report();
$orgs = platform_org_stats();
$extended = admin_extended_stats();

require_once APP_ROOT . '/includes/header.php';
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.reports'); ?></h1>
        <div class="admin-kpi-grid">
            <article class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin.total_revenue'); ?></div><div class="admin-kpi-value"><?= e(format_currency($revenue['total'])) ?></div></article>
            <article class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin.month_revenue'); ?></div><div class="admin-kpi-value"><?= e(format_currency($revenue['month'])) ?></div></article>
            <article class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin.pending_reports'); ?></div><div class="admin-kpi-value"><?= (int) $extended['pending_reports'] ?></div></article>
            <article class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin.open_tickets'); ?></div><div class="admin-kpi-value"><?= (int) $extended['open_tickets'] ?></div></article>
        </div>
        <div class="card" style="margin-top:1.5rem;"><h2><?php _e('admin.revenue_by_org'); ?></h2>
            <table class="table"><thead><tr><th><?php _e('sidebar.organizations'); ?></th><th><?php _e('admin.revenue'); ?></th></tr></thead>
            <tbody><?php foreach ($revenue['by_org'] as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e(format_currency((float) $row['revenue'])) ?></td></tr><?php endforeach; ?></tbody></table>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
