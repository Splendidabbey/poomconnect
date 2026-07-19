<?php

declare(strict_types=1);

function ensure_admin_users_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();
    $cols = [
        'account_status' => "ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER role",
        'deactivated_at' => 'TIMESTAMP NULL AFTER account_status',
        'deactivated_by' => 'INT UNSIGNED NULL AFTER deactivated_at',
        'admin_notes' => 'TEXT NULL AFTER deactivated_by',
        'last_login_at' => 'TIMESTAMP NULL AFTER admin_notes',
    ];

    foreach ($cols as $col => $def) {
        if (!table_has_column('users', $col)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$col} {$def}");
        }
    }

    $ready = true;
}

function user_account_status(array $user): string
{
    return $user['account_status'] ?? 'active';
}

function user_is_active(array $user): bool
{
    return user_account_status($user) === 'active';
}

function assignable_user_roles(?array $admin = null): array
{
    $admin ??= current_user();
    if (!$admin) {
        return [];
    }

    if (is_super_admin()) {
        return ['participant', 'organizer', 'moderator', 'admin', 'super_admin'];
    }

    return ['participant', 'organizer', 'moderator'];
}

function admin_can_manage_user(array $target, ?array $admin = null, string $action = 'edit'): bool
{
    $admin ??= current_user();
    if (!$admin || !is_admin()) {
        return false;
    }

    $targetId = (int) $target['id'];
    $adminId = (int) $admin['id'];

    if ($action === 'delete' && $targetId === $adminId) {
        return false;
    }

    if ($action === 'deactivate' && $targetId === $adminId) {
        return false;
    }

    if (in_array($target['role'], ['admin', 'super_admin'], true) && !is_super_admin()) {
        return false;
    }

    if ($action === 'role' && in_array($target['role'], ['admin', 'super_admin'], true) && !is_super_admin()) {
        return false;
    }

    if ($target['role'] === 'super_admin' && !is_super_admin()) {
        return false;
    }

    return true;
}

function super_admin_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND account_status = 'active'")->fetchColumn();
}

function get_admin_user_stats(): array
{
    $pdo = db();

    return [
        'total' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'active' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'active' OR account_status IS NULL")->fetchColumn(),
        'inactive' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'inactive'")->fetchColumn(),
        'organizers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'")->fetchColumn(),
        'participants' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'participant'")->fetchColumn(),
        'verified' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE verified_at IS NOT NULL')->fetchColumn(),
    ];
}

function get_admin_users(array $filters = [], int $limit = 100): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $term = '%' . trim($filters['q']) . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $allowedRoles = array_merge(assignable_user_roles(), ['admin', 'super_admin']);
    if (!empty($filters['role']) && in_array($filters['role'], $allowedRoles, true)) {
        $where[] = 'u.role = ?';
        $params[] = $filters['role'];
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
        if ($filters['status'] === 'active') {
            $where[] = "(u.account_status = 'active' OR u.account_status IS NULL)";
        } else {
            $where[] = "u.account_status = 'inactive'";
        }
    }

    if (!empty($filters['verified'])) {
        $where[] = $filters['verified'] === 'yes' ? 'u.verified_at IS NOT NULL' : 'u.verified_at IS NULL';
    }

    $sql = 'SELECT u.*,
            (SELECT COUNT(*) FROM organizations o WHERE o.owner_id = u.id) AS orgs_owned,
            (SELECT COUNT(*) FROM organization_members om WHERE om.user_id = u.id) AS org_memberships,
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.user_id = u.id) AS events_joined
            FROM users u
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY u.created_at DESC
            LIMIT ' . (int) $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function get_admin_user(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function get_user_access_profile(int $userId): array
{
    $pdo = db();

    $orgsOwned = $pdo->prepare(
        'SELECT o.id, o.name, o.status, o.slug, sp.name AS plan_name
         FROM organizations o
         LEFT JOIN subscription_plans sp ON sp.id = o.subscription_plan_id
         WHERE o.owner_id = ?
         ORDER BY o.created_at DESC'
    );
    $orgsOwned->execute([$userId]);
    $orgsOwned = $orgsOwned->fetchAll();

    $memberships = $pdo->prepare(
        'SELECT om.member_role, o.id, o.name, o.status
         FROM organization_members om
         JOIN organizations o ON o.id = om.organization_id
         WHERE om.user_id = ?
         ORDER BY o.name ASC'
    );
    $memberships->execute([$userId]);
    $memberships = $memberships->fetchAll();

    $eventsCreated = $pdo->prepare(
        'SELECT COUNT(*) FROM events WHERE created_by = ?'
    );
    $eventsCreated->execute([$userId]);
    $eventsCreated = (int) $eventsCreated->fetchColumn();

    $registrations = $pdo->prepare(
        "SELECT e.id, e.title, e.event_date, ep.registration_status, ep.payment_status
         FROM event_participants ep
         JOIN events e ON e.id = ep.event_id
         WHERE ep.user_id = ?
         ORDER BY e.event_date DESC
         LIMIT 12"
    );
    $registrations->execute([$userId]);
    $registrations = $registrations->fetchAll();

    $payments = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE user_id = ?');
    $payments->execute([$userId]);
    $paymentCount = (int) $payments->fetchColumn();

    $tickets = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE user_id = ?');
    $tickets->execute([$userId]);
    $ticketCount = (int) $tickets->fetchColumn();

    $reportsFiled = $pdo->prepare('SELECT COUNT(*) FROM user_reports WHERE reporter_id = ?');
    $reportsFiled->execute([$userId]);
    $reportsFiled = (int) $reportsFiled->fetchColumn();

    $reportsReceived = $pdo->prepare('SELECT COUNT(*) FROM user_reports WHERE reported_id = ?');
    $reportsReceived->execute([$userId]);
    $reportsReceived = (int) $reportsReceived->fetchColumn();

    $supportTickets = $pdo->prepare('SELECT COUNT(*) FROM support_tickets WHERE user_id = ?');
    $supportTickets->execute([$userId]);
    $supportTickets = (int) $supportTickets->fetchColumn();

    return [
        'orgs_owned' => $orgsOwned,
        'memberships' => $memberships,
        'events_created' => $eventsCreated,
        'registrations' => $registrations,
        'payment_count' => $paymentCount,
        'ticket_count' => $ticketCount,
        'reports_filed' => $reportsFiled,
        'reports_received' => $reportsReceived,
        'support_tickets' => $supportTickets,
    ];
}

function deactivate_user(int $userId, int $adminId): bool
{
    $user = get_admin_user($userId);
    if (!$user || !admin_can_manage_user($user, null, 'deactivate')) {
        return false;
    }

    db()->prepare(
        "UPDATE users SET account_status = 'inactive', deactivated_at = NOW(), deactivated_by = ?, updated_at = NOW() WHERE id = ?"
    )->execute([$adminId, $userId]);

    log_admin_action($adminId, 'user_deactivated', 'User ID: ' . $userId . ' (' . $user['email'] . ')');

    return true;
}

function activate_user(int $userId, int $adminId): bool
{
    $user = get_admin_user($userId);
    if (!$user || !admin_can_manage_user($user)) {
        return false;
    }

    db()->prepare(
        "UPDATE users SET account_status = 'active', deactivated_at = NULL, deactivated_by = NULL, updated_at = NOW() WHERE id = ?"
    )->execute([$userId]);

    log_admin_action($adminId, 'user_activated', 'User ID: ' . $userId . ' (' . $user['email'] . ')');

    return true;
}

function delete_user(int $userId, int $adminId): array
{
    $user = get_admin_user($userId);
    if (!$user || !admin_can_manage_user($user, null, 'delete')) {
        return ['ok' => false, 'error' => __('admin_users.cannot_delete')];
    }

    if ($user['role'] === 'super_admin' && super_admin_count() <= 1) {
        return ['ok' => false, 'error' => __('admin_users.last_super_admin')];
    }

    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    log_admin_action($adminId, 'user_deleted', 'Deleted user ID: ' . $userId . ' (' . $user['email'] . ')');

    return ['ok' => true];
}

function update_user_role(int $userId, string $role, int $adminId): array
{
    $user = get_admin_user($userId);
    if (!$user || !admin_can_manage_user($user, null, 'role')) {
        return ['ok' => false, 'error' => __('admin_users.cannot_change_role')];
    }

    if (!in_array($role, assignable_user_roles(), true)) {
        return ['ok' => false, 'error' => __('admin_users.invalid_role')];
    }

    if ($user['role'] === 'super_admin' && $role !== 'super_admin' && super_admin_count() <= 1) {
        return ['ok' => false, 'error' => __('admin_users.last_super_admin')];
    }

    db()->prepare('UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?')->execute([$role, $userId]);
    log_admin_action($adminId, 'user_role_changed', 'User ID: ' . $userId . ' → ' . $role);

    return ['ok' => true];
}

function save_user_admin_notes(int $userId, string $notes, int $adminId): bool
{
    $user = get_admin_user($userId);
    if (!$user || !admin_can_manage_user($user)) {
        return false;
    }

    db()->prepare('UPDATE users SET admin_notes = ?, updated_at = NOW() WHERE id = ?')
        ->execute([trim($notes) ?: null, $userId]);

    log_admin_action($adminId, 'user_notes_updated', 'User ID: ' . $userId);

    return true;
}

function toggle_user_vip(int $userId, int $adminId): bool
{
    $user = get_admin_user($userId);
    if (!$user || !admin_can_manage_user($user)) {
        return false;
    }

    $newValue = empty($user['is_vip']) ? 1 : 0;
    db()->prepare('UPDATE users SET is_vip = ?, updated_at = NOW() WHERE id = ?')->execute([$newValue, $userId]);
    log_admin_action($adminId, $newValue ? 'user_vip_enabled' : 'user_vip_disabled', 'User ID: ' . $userId);

    return true;
}

function record_user_login(int $userId): void
{
    if (!table_has_column('users', 'last_login_at')) {
        return;
    }

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$userId]);
}

function handle_admin_user_action(array $post, int $adminId): array
{
    $userId = (int) ($post['user_id'] ?? 0);
    $action = $post['action'] ?? '';

    if ($userId <= 0) {
        return ['ok' => false, 'error' => __('admin_users.not_found')];
    }

    return match ($action) {
        'deactivate' => deactivate_user($userId, $adminId)
            ? ['ok' => true, 'message' => __('admin_users.deactivated')]
            : ['ok' => false, 'error' => __('admin_users.action_failed')],
        'activate' => activate_user($userId, $adminId)
            ? ['ok' => true, 'message' => __('admin_users.activated')]
            : ['ok' => false, 'error' => __('admin_users.action_failed')],
        'delete' => delete_user($userId, $adminId),
        'verify' => (function () use ($userId, $adminId) {
            verify_user($userId);
            log_admin_action($adminId, 'user_verified', 'User ID: ' . $userId);

            return ['ok' => true, 'message' => __('admin_users.verified')];
        })(),
        'unverify' => (function () use ($userId, $adminId) {
            unverify_user($userId);
            log_admin_action($adminId, 'user_unverified', 'User ID: ' . $userId);

            return ['ok' => true, 'message' => __('admin_users.unverified')];
        })(),
        'toggle_vip' => toggle_user_vip($userId, $adminId)
            ? ['ok' => true, 'message' => __('admin_users.vip_updated')]
            : ['ok' => false, 'error' => __('admin_users.action_failed')],
        'save_notes' => save_user_admin_notes($userId, (string) ($post['admin_notes'] ?? ''), $adminId)
            ? ['ok' => true, 'message' => __('admin_users.notes_saved')]
            : ['ok' => false, 'error' => __('admin_users.action_failed')],
        'change_role' => update_user_role($userId, (string) ($post['role'] ?? ''), $adminId),
        default => ['ok' => false, 'error' => __('admin_users.invalid_action')],
    };
}

function user_role_label(string $role): string
{
    $key = 'admin_users.role_' . $role;
    $label = __($key);

    return $label !== $key ? $label : ucfirst(str_replace('_', ' ', $role));
}
