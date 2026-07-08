<?php

declare(strict_types=1);

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;

    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

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

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if (!in_array($user['role'], ['organizer', 'admin', 'super_admin'], true)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];

    return true;
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
        set_flash('error', 'Please log in to continue.');
        redirect(base_url('login.php'));
    }

    if ($roles !== [] && !in_array(current_user_role(), $roles, true)) {
        set_flash('error', 'You do not have permission to access this page.');
        redirect(base_url('login.php'));
    }
}

function require_organizer(): void
{
    require_login(['organizer', 'admin', 'super_admin']);
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
    return in_array(current_user_role(), ['organizer', 'admin', 'super_admin'], true);
}
