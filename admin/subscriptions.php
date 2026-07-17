<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.subscriptions');
$stats = platform_subscription_stats();
$plans = get_subscription_plans();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['org_id'], $_POST['plan_slug'])) {
    assign_org_plan((int) $_POST['org_id'], (string) $_POST['plan_slug']);
    log_admin_action((int) current_user()['id'], 'subscription_assigned', 'org=' . (int) $_POST['org_id']);
    set_flash('success', __('subscription.plan_updated'));
    redirect(base_url('admin/subscriptions.php'));
}

$orgs = db()->query('SELECT id, name FROM organizations ORDER BY name ASC')->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.subscriptions'); ?></h1>
        <div class="admin-kpi-grid">
            <article class="admin-kpi-card"><div class="admin-kpi-label">MRR</div><div class="admin-kpi-value"><?= e(format_currency($stats['mrr'])) ?></div></article>
        </div>
        <div class="card" style="margin-top:1.5rem;">
            <table class="table"><thead><tr><th><?php _e('subscription.plan'); ?></th><th><?php _e('admin.organizations'); ?></th><th>MRR</th></tr></thead>
            <tbody><?php foreach ($stats['plans'] as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= (int) $p['org_count'] ?></td><td><?= e(format_currency((float) $p['mrr'])) ?></td></tr><?php endforeach; ?></tbody></table>
        </div>
        <div class="card" style="margin-top:1.5rem;">
            <h2><?php _e('admin.assign_plan'); ?></h2>
            <form method="post" class="form-row">
                <select name="org_id" class="select" required><?php foreach ($orgs as $o): ?><option value="<?= (int) $o['id'] ?>"><?= e($o['name']) ?></option><?php endforeach; ?></select>
                <select name="plan_slug" class="select" required><?php foreach ($plans as $p): ?><option value="<?= e($p['slug']) ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
                <button class="btn btn-primary"><?php _e('common.save'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
