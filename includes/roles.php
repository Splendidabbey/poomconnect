<?php

declare(strict_types=1);

function ensure_roles_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    try {
        $pdo->exec(
            "ALTER TABLE users MODIFY role ENUM('participant','organizer','moderator','admin','super_admin') NOT NULL DEFAULT 'participant'"
        );
    } catch (PDOException) {
        // already migrated
    }

    $userCols = [
        'verified_at' => 'TIMESTAMP NULL AFTER is_vip',
        'emergency_contact_name' => 'VARCHAR(150) NULL AFTER verified_at',
        'emergency_contact_phone' => 'VARCHAR(30) NULL AFTER emergency_contact_name',
        'country' => 'CHAR(2) NULL AFTER emergency_contact_phone',
    ];
    foreach ($userCols as $col => $def) {
        if (!table_has_column('users', $col)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$col} {$def}");
        }
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS organization_members (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            member_role ENUM('owner','admin','staff','moderator') NOT NULL DEFAULT 'staff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_org_member (organization_id, user_id),
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ready = true;
}

function is_super_admin(): bool
{
    return current_user_role() === 'super_admin';
}

function is_moderator(): bool
{
    return in_array(current_user_role(), ['moderator', 'admin', 'super_admin'], true);
}

function require_super_admin(): void
{
    require_login(['super_admin']);
}

function require_moderator(): void
{
    require_login(['moderator', 'admin', 'super_admin']);
}

function user_is_verified(?array $user = null): bool
{
    $user ??= current_user();

    return !empty($user['verified_at']);
}

function verify_user(int $userId): void
{
    db()->prepare('UPDATE users SET verified_at = COALESCE(verified_at, NOW()) WHERE id = ?')->execute([$userId]);
}

function unverify_user(int $userId): void
{
    db()->prepare('UPDATE users SET verified_at = NULL WHERE id = ?')->execute([$userId]);
}

function user_org_membership(int $userId, int $orgId): ?array
{
    $stmt = db()->prepare('SELECT * FROM organization_members WHERE organization_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$orgId, $userId]);

    return $stmt->fetch() ?: null;
}

function add_org_member(int $orgId, int $userId, string $role = 'staff'): void
{
    db()->prepare('INSERT IGNORE INTO organization_members (organization_id, user_id, member_role) VALUES (?, ?, ?)')
        ->execute([$orgId, $userId, $role]);
}

function user_belongs_to_org(int $userId, int $orgId): bool
{
    $org = db()->prepare('SELECT id FROM organizations WHERE id = ? AND owner_id = ? LIMIT 1');
    $org->execute([$orgId, $userId]);
    if ($org->fetch()) {
        return true;
    }

    return user_org_membership($userId, $orgId) !== null;
}
