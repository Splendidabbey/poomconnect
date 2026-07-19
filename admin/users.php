<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.users_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
    'verified' => $_GET['verified'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $result = handle_admin_user_action($_POST, (int) current_user()['id']);
    if ($result['ok']) {
        set_flash('success', $result['message'] ?? __('admin_users.action_done'));
    } else {
        set_flash('error', $result['error'] ?? __('admin_users.action_failed'));
    }
    redirect(base_url('admin/users.php?' . http_build_query(array_filter($filters))));
}

$stats = get_admin_user_stats();
$users = get_admin_users($filters, 200);
$roleOptions = array_merge(assignable_user_roles(), is_super_admin() ? ['admin', 'super_admin'] : []);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header admin-page-header">
            <div>
                <h1><?php _e('admin.all_users'); ?></h1>
                <p class="text-muted"><?php _e('admin_users.subtitle'); ?></p>
            </div>
        </div>

        <div class="admin-kpi-grid admin-user-kpi">
            <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin.total_users'); ?></div><div class="admin-kpi-value"><?= number_format($stats['total']) ?></div></div>
            <div class="admin-kpi-card is-success"><div class="admin-kpi-label"><?php _e('admin_users.active_users'); ?></div><div class="admin-kpi-value"><?= number_format($stats['active']) ?></div></div>
            <div class="admin-kpi-card is-warning"><div class="admin-kpi-label"><?php _e('admin_users.inactive_users'); ?></div><div class="admin-kpi-value"><?= number_format($stats['inactive']) ?></div></div>
            <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin_users.organizers'); ?></div><div class="admin-kpi-value"><?= number_format($stats['organizers']) ?></div></div>
            <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('safety.verified'); ?></div><div class="admin-kpi-value"><?= number_format($stats['verified']) ?></div></div>
        </div>

        <div class="card admin-filter-card">
            <form method="get" class="admin-user-filters">
                <div class="form-group">
                    <label><?php _e('events_page.search'); ?></label>
                    <input type="search" name="q" class="input" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('admin_users.search_placeholder')) ?>">
                </div>
                <div class="form-group">
                    <label><?php _e('admin.role'); ?></label>
                    <select name="role" class="select">
                        <option value=""><?php _e('admin_users.all_roles'); ?></option>
                        <?php foreach ($roleOptions as $role): ?>
                            <option value="<?= e($role) ?>" <?= $filters['role'] === $role ? 'selected' : '' ?>><?= e(user_role_label($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php _e('common.status'); ?></label>
                    <select name="status" class="select">
                        <option value=""><?php _e('admin_users.all_statuses'); ?></option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>><?php _e('admin_users.status_active'); ?></option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>><?php _e('admin_users.status_inactive'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php _e('safety.verified'); ?></label>
                    <select name="verified" class="select">
                        <option value=""><?php _e('admin_users.all_users'); ?></option>
                        <option value="yes" <?= $filters['verified'] === 'yes' ? 'selected' : '' ?>><?php _e('admin_users.verified_only'); ?></option>
                        <option value="no" <?= $filters['verified'] === 'no' ? 'selected' : '' ?>><?php _e('admin_users.unverified_only'); ?></option>
                    </select>
                </div>
                <div class="form-group admin-filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm"><?php _e('events_page.apply_filters'); ?></button>
                    <a href="<?= base_url('admin/users.php') ?>" class="btn btn-outline btn-sm"><?php _e('events_page.clear_filters'); ?></a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table class="table admin-users-table">
                    <thead>
                        <tr>
                            <th><?php _e('organizer.name'); ?></th>
                            <th><?php _e('auth.email'); ?></th>
                            <th><?php _e('admin.role'); ?></th>
                            <th><?php _e('common.status'); ?></th>
                            <th><?php _e('admin_users.access'); ?></th>
                            <th><?php _e('admin.joined'); ?></th>
                            <th><?php _e('common.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users): ?>
                            <?php foreach ($users as $u): ?>
                                <?php
                                $isActive = user_is_active($u);
                                $canManage = admin_can_manage_user($u);
                                ?>
                                <tr class="<?= $isActive ? '' : 'row-inactive' ?>">
                                    <td>
                                        <strong><?= e($u['full_name']) ?></strong>
                                        <?php if (!empty($u['is_vip'])): ?><span class="badge badge-purple">VIP</span><?php endif; ?>
                                        <?php if (!empty($u['verified_at'])): ?><span class="badge badge-success"><?php _e('safety.verified'); ?></span><?php endif; ?>
                                    </td>
                                    <td><?= e($u['email']) ?></td>
                                    <td><span class="badge badge-outline"><?= e(user_role_label($u['role'])) ?></span></td>
                                    <td>
                                        <span class="badge badge-<?= $isActive ? 'success' : 'warning' ?>">
                                            <?= e($isActive ? __('admin_users.status_active') : __('admin_users.status_inactive')) ?>
                                        </span>
                                    </td>
                                    <td class="admin-access-cell">
                                        <span><?= (int) $u['orgs_owned'] ?> <?php _e('admin_users.orgs'); ?></span>
                                        <span><?= (int) $u['events_joined'] ?> <?php _e('admin_users.events'); ?></span>
                                    </td>
                                    <td><?= e(format_date($u['created_at'])) ?></td>
                                    <td class="admin-actions-cell">
                                        <a href="<?= base_url('admin/user.php?id=' . (int) $u['id']) ?>" class="btn btn-outline btn-sm"><?php _e('admin_users.manage'); ?></a>
                                        <?php if ($canManage): ?>
                                            <form method="post" class="admin-inline-form" onsubmit="return confirm(<?= json_encode($isActive ? __('admin_users.confirm_deactivate') : __('admin_users.confirm_activate')) ?>)">
                                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                <button type="submit" name="action" value="<?= $isActive ? 'deactivate' : 'activate' ?>" class="btn btn-outline btn-sm">
                                                    <?= e($isActive ? __('admin_users.deactivate') : __('admin_users.activate')) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7"><?php _e('admin_users.no_results'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
