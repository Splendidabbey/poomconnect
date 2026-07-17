<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.organizations_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

$orgs = db()->query(
    'SELECT o.*, u.full_name AS owner_name, sp.name AS plan_name FROM organizations o
     JOIN users u ON u.id = o.owner_id
     LEFT JOIN subscription_plans sp ON sp.id = o.subscription_plan_id
     ORDER BY o.created_at DESC'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['org_id'], $_POST['action'])) {
    $orgId = (int) $_POST['org_id'];
    if ($_POST['action'] === 'suspend') {
        suspend_organization($orgId);
    } elseif ($_POST['action'] === 'activate') {
        activate_organization($orgId);
    }
    redirect(base_url('admin/organizations.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header"><h1><?php _e('admin.organizations_title'); ?></h1></div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th><?php _e('organizer.name'); ?></th><th><?php _e('admin.owner'); ?></th><th><?php _e('subscription.plan'); ?></th><th><?php _e('common.status'); ?></th><th><?php _e('tenant.custom_domain'); ?></th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orgs as $org): ?>
                            <tr>
                                <td><a href="<?= e(org_public_url($org)) ?>" target="_blank"><?= e($org['name']) ?></a></td>
                                <td><?= e($org['owner_name']) ?></td>
                                <td><?= e($org['plan_name'] ?? 'Starter') ?></td>
                                <td><span class="badge badge-<?= ($org['status'] ?? 'active') === 'active' ? 'success' : 'warning' ?>"><?= e(status_label($org['status'] ?? 'active')) ?></span></td>
                                <td><?= e($org['custom_domain'] ?? $org['subdomain'] ?? '—') ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="org_id" value="<?= (int) $org['id'] ?>">
                                        <?php if (($org['status'] ?? 'active') === 'active'): ?>
                                            <button name="action" value="suspend" class="btn btn-outline btn-sm"><?php _e('admin.suspend'); ?></button>
                                        <?php else: ?>
                                            <button name="action" value="activate" class="btn btn-primary btn-sm"><?php _e('admin.activate'); ?></button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
