<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$userId = (int) ($_GET['id'] ?? 0);
$user = $userId ? get_admin_user($userId) : null;

if (!$user) {
    set_flash('error', __('admin_users.not_found'));
    redirect(base_url('admin/users.php'));
}

$pageTitle = __('admin_users.profile_title', ['name' => $user['full_name']]);
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;
$canManage = admin_can_manage_user($user);
$canDelete = admin_can_manage_user($user, null, 'delete');
$access = get_user_access_profile($userId);
$roleOptions = assignable_user_roles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['user_id'] = (string) $userId;
    $result = handle_admin_user_action($_POST, (int) current_user()['id']);

    if ($result['ok']) {
        if (($_POST['action'] ?? '') === 'delete') {
            set_flash('success', __('admin_users.deleted'));
            redirect(base_url('admin/users.php'));
        }
        set_flash('success', $result['message'] ?? __('admin_users.action_done'));
    } else {
        set_flash('error', $result['error'] ?? __('admin_users.action_failed'));
    }

    redirect(base_url('admin/user.php?id=' . $userId));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header admin-page-header">
            <div>
                <a href="<?= base_url('admin/users.php') ?>" class="text-muted">← <?php _e('admin.all_users'); ?></a>
                <h1><?= e($user['full_name']) ?></h1>
                <p class="text-muted"><?= e($user['email']) ?></p>
            </div>
            <div class="admin-user-badges">
                <span class="badge badge-outline"><?= e(user_role_label($user['role'])) ?></span>
                <span class="badge badge-<?= user_is_active($user) ? 'success' : 'warning' ?>">
                    <?= e(user_is_active($user) ? __('admin_users.status_active') : __('admin_users.status_inactive')) ?>
                </span>
                <?php if (!empty($user['verified_at'])): ?><span class="badge badge-success"><?php _e('safety.verified'); ?></span><?php endif; ?>
                <?php if (!empty($user['is_vip'])): ?><span class="badge badge-purple">VIP</span><?php endif; ?>
            </div>
        </div>

        <div class="admin-user-grid">
            <div class="card">
                <h2><?php _e('admin_users.account_controls'); ?></h2>
                <?php if ($canManage): ?>
                    <div class="admin-control-stack">
                        <form method="post" class="admin-control-form" onsubmit="return confirm(<?= json_encode(user_is_active($user) ? __('admin_users.confirm_deactivate') : __('admin_users.confirm_activate')) ?>)">
                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                            <button type="submit" name="action" value="<?= user_is_active($user) ? 'deactivate' : 'activate' ?>" class="btn btn-outline">
                                <?= e(user_is_active($user) ? __('admin_users.deactivate') : __('admin_users.activate')) ?>
                            </button>
                        </form>

                        <form method="post" class="admin-control-form">
                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                            <button type="submit" name="action" value="<?= !empty($user['verified_at']) ? 'unverify' : 'verify' ?>" class="btn btn-outline">
                                <?= e(!empty($user['verified_at']) ? __('admin_users.unverify') : __('admin_users.verify_user')) ?>
                            </button>
                        </form>

                        <form method="post" class="admin-control-form">
                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                            <button type="submit" name="action" value="toggle_vip" class="btn btn-outline">
                                <?= e(!empty($user['is_vip']) ? __('admin_users.remove_vip') : __('admin_users.make_vip')) ?>
                            </button>
                        </form>

                        <?php if ($canManage): ?>
                            <form method="post" class="admin-control-form admin-role-form">
                                <input type="hidden" name="user_id" value="<?= $userId ?>">
                                <label><?php _e('admin_users.change_role'); ?></label>
                                <div class="form-inline-row">
                                    <select name="role" class="select">
                                        <?php foreach ($roleOptions as $role): ?>
                                            <option value="<?= e($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= e(user_role_label($role)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="action" value="change_role" class="btn btn-primary btn-sm"><?php _e('common.save'); ?></button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <?php if ($canDelete): ?>
                            <form method="post" class="admin-control-form admin-delete-form" onsubmit="return confirm(<?= json_encode(__('admin_users.confirm_delete')) ?>)">
                                <input type="hidden" name="user_id" value="<?= $userId ?>">
                                <button type="submit" name="action" value="delete" class="btn btn-outline btn-danger"><?php _e('admin_users.delete_user'); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><?php _e('admin_users.limited_access'); ?></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><?php _e('admin_users.profile_details'); ?></h2>
                <dl class="admin-detail-list">
                    <div><dt><?php _e('auth.email'); ?></dt><dd><?= e($user['email']) ?></dd></div>
                    <div><dt><?php _e('register_page.phone'); ?></dt><dd><?= e($user['phone'] ?? '—') ?></dd></div>
                    <div><dt><?php _e('events_page.city'); ?></dt><dd><?= e($user['city'] ?? '—') ?></dd></div>
                    <div><dt><?php _e('tenant.country'); ?></dt><dd><?= e($user['country'] ?? '—') ?></dd></div>
                    <div><dt><?php _e('loyalty.points'); ?></dt><dd><?= (int) $user['loyalty_points'] ?> · <?= e($user['loyalty_level']) ?></dd></div>
                    <div><dt><?php _e('admin.joined'); ?></dt><dd><?= e(format_date($user['created_at'])) ?></dd></div>
                    <div><dt><?php _e('admin_users.last_login'); ?></dt><dd><?= e(!empty($user['last_login_at']) ? format_date($user['last_login_at']) : '—') ?></dd></div>
                    <?php if (!empty($user['deactivated_at'])): ?>
                        <div><dt><?php _e('admin_users.deactivated_at'); ?></dt><dd><?= e(format_date($user['deactivated_at'])) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="card">
                <h2><?php _e('admin_users.access_overview'); ?></h2>
                <div class="admin-kpi-grid admin-user-kpi">
                    <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin_users.orgs_owned'); ?></div><div class="admin-kpi-value"><?= count($access['orgs_owned']) ?></div></div>
                    <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin_users.memberships'); ?></div><div class="admin-kpi-value"><?= count($access['memberships']) ?></div></div>
                    <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('admin_users.events_joined'); ?></div><div class="admin-kpi-value"><?= count($access['registrations']) ?></div></div>
                    <div class="admin-kpi-card"><div class="admin-kpi-label"><?php _e('sidebar.payments'); ?></div><div class="admin-kpi-value"><?= $access['payment_count'] ?></div></div>
                </div>
            </div>

            <?php if ($canManage): ?>
                <div class="card admin-user-grid-full">
                    <h2><?php _e('admin_users.admin_notes'); ?></h2>
                    <form method="post">
                        <input type="hidden" name="user_id" value="<?= $userId ?>">
                        <textarea name="admin_notes" class="input" rows="4" placeholder="<?= e(__('admin_users.notes_placeholder')) ?>"><?= e($user['admin_notes'] ?? '') ?></textarea>
                        <button type="submit" name="action" value="save_notes" class="btn btn-primary btn-sm" style="margin-top:0.75rem;"><?php _e('common.save'); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($access['orgs_owned']): ?>
                <div class="card admin-user-grid-full">
                    <h2><?php _e('admin_users.orgs_owned'); ?></h2>
                    <table class="table">
                        <thead><tr><th><?php _e('organizer.name'); ?></th><th><?php _e('subscription.plan'); ?></th><th><?php _e('common.status'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($access['orgs_owned'] as $org): ?>
                                <tr>
                                    <td><a href="<?= e(org_public_url($org)) ?>" target="_blank"><?= e($org['name']) ?></a></td>
                                    <td><?= e($org['plan_name'] ?? 'Starter') ?></td>
                                    <td><?= e(status_label($org['status'] ?? 'active')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($access['memberships']): ?>
                <div class="card admin-user-grid-full">
                    <h2><?php _e('admin_users.org_memberships'); ?></h2>
                    <table class="table">
                        <thead><tr><th><?php _e('organizer.name'); ?></th><th><?php _e('admin_users.member_role'); ?></th><th><?php _e('common.status'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($access['memberships'] as $row): ?>
                                <tr>
                                    <td><?= e($row['name']) ?></td>
                                    <td><?= e($row['member_role']) ?></td>
                                    <td><?= e(status_label($row['status'] ?? 'active')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($access['registrations']): ?>
                <div class="card admin-user-grid-full">
                    <h2><?php _e('admin_users.recent_registrations'); ?></h2>
                    <table class="table">
                        <thead><tr><th><?php _e('organizer.event'); ?></th><th><?php _e('common.date'); ?></th><th><?php _e('common.status'); ?></th><th><?php _e('ticket_page.status'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($access['registrations'] as $row): ?>
                                <tr>
                                    <td><?= e($row['title']) ?></td>
                                    <td><?= e(format_date($row['event_date'])) ?></td>
                                    <td><?= e(status_label($row['registration_status'])) ?></td>
                                    <td><?= e(status_label($row['payment_status'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
