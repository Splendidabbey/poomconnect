<?php

declare(strict_types=1);

function current_user(bool $refresh = false): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    static $loaded = false;

    if ($refresh) {
        $loaded = false;
        $user = null;
    }

    if ($loaded) {
        return $user;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    $loaded = true;

    return $user;
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_role(): ?string
{
    return current_user()['role'] ?? null;
}

function member_capable_roles(): array
{
    return ['participant', 'organizer', 'moderator', 'admin', 'super_admin'];
}

function role_can_join_events(?string $role): bool
{
    return $role !== null && in_array($role, member_capable_roles(), true);
}

function role_can_host_events(?string $role): bool
{
    return role_can_join_events($role);
}

function is_member(): bool
{
    return is_logged_in();
}

function can_join_events(): bool
{
    return role_can_join_events(current_user_role());
}

function can_host_events(): bool
{
    return role_can_host_events(current_user_role());
}

function member_home_url(): string
{
    if (is_admin()) {
        return base_url('admin/dashboard.php');
    }

    return base_url('my-events.php');
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(string $email, string $password, bool $allowParticipant = true): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if (!user_is_active($user)) {
        return false;
    }

    if (!role_can_join_events($user['role'] ?? null)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['participant_user_id'] = (int) $user['id'];
    current_user(true);

    if (function_exists('record_user_login')) {
        record_user_login((int) $user['id']);
    }

    return true;
}

function login_participant(string $email, string $password): bool
{
    return login_user($email, $password, true);
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function require_login(array $roles = []): void
{
    if (!is_logged_in()) {
        set_flash('error', __('auth.login_required'));
        redirect(base_url('login.php'));
    }

    $user = current_user();
    if ($user && function_exists('user_is_active') && !user_is_active($user)) {
        logout_user();
        set_flash('error', __('admin_users.account_inactive'));
        redirect(base_url('login.php'));
    }

    if ($roles !== [] && !in_array(current_user_role(), $roles, true)) {
        set_flash('error', __('auth.permission_denied'));
        redirect(base_url('login.php'));
    }
}

function require_member(): void
{
    require_login(member_capable_roles());
}

function require_organizer(): void
{
    require_member();
    if (is_admin()) {
        return;
    }
    ensure_member_organization();
}

function require_admin(): void
{
    require_login(['admin', 'super_admin']);
}

function is_admin(): bool
{
    return in_array(current_user_role(), ['admin', 'super_admin'], true);
}

function is_organizer(): bool
{
    if (!is_logged_in()) {
        return false;
    }

    if (in_array(current_user_role(), ['organizer', 'admin', 'super_admin'], true)) {
        return true;
    }

    return get_organization_for_user((int) current_user()['id']) !== null;
}

function require_participant(): void
{
    require_member();
}

function is_participant(): bool
{
    return can_join_events();
}

function is_guest(): bool
{
    return !is_logged_in();
}
