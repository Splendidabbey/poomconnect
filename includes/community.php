<?php

declare(strict_types=1);

function ensure_community_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS communities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(160) NOT NULL,
            description TEXT NULL,
            cover_image VARCHAR(255) NULL,
            is_public TINYINT(1) NOT NULL DEFAULT 1,
            member_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_community_slug (organization_id, slug),
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS community_members (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            community_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            member_role ENUM('member','moderator') NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_community_member (community_id, user_id),
            FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS organizer_followers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            follower_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (organization_id, follower_id),
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS recurring_event_series (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            title VARCHAR(200) NOT NULL,
            template_event_id INT UNSIGNED NULL,
            frequency ENUM('weekly','biweekly','monthly') NOT NULL DEFAULT 'weekly',
            next_date DATE NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (template_event_id) REFERENCES events(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!table_has_column('events', 'series_id')) {
        $pdo->exec('ALTER TABLE events ADD COLUMN series_id INT UNSIGNED NULL AFTER round_count');
        $pdo->exec('ALTER TABLE events ADD COLUMN is_recurring_instance TINYINT(1) NOT NULL DEFAULT 0 AFTER series_id');
    }

    $ready = true;
}

function get_org_communities(int $orgId): array
{
    $stmt = db()->prepare('SELECT * FROM communities WHERE organization_id = ? ORDER BY created_at DESC');
    $stmt->execute([$orgId]);

    return $stmt->fetchAll();
}

function get_community_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT c.*, o.name AS org_name FROM communities c JOIN organizations o ON o.id = c.organization_id WHERE c.id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function create_community(int $orgId, string $name, string $description = '', bool $isPublic = true): int
{
    $slug = unique_community_slug($orgId, $name);
    db()->prepare('INSERT INTO communities (organization_id, name, slug, description, is_public) VALUES (?, ?, ?, ?, ?)')
        ->execute([$orgId, $name, $slug, $description ?: null, $isPublic ? 1 : 0]);

    return (int) db()->lastInsertId();
}

function unique_community_slug(int $orgId, string $name): string
{
    $base = slugify($name) ?: 'group';
    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM communities WHERE organization_id = ? AND slug = ? LIMIT 1');
        $stmt->execute([$orgId, $slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function join_community(int $communityId, int $userId): void
{
    $ins = db()->prepare('INSERT IGNORE INTO community_members (community_id, user_id) VALUES (?, ?)');
    $ins->execute([$communityId, $userId]);
    if ($ins->rowCount() > 0) {
        db()->prepare('UPDATE communities SET member_count = member_count + 1 WHERE id = ?')->execute([$communityId]);
    }
}

function leave_community(int $communityId, int $userId): void
{
    $del = db()->prepare('DELETE FROM community_members WHERE community_id = ? AND user_id = ?');
    $del->execute([$communityId, $userId]);
    if ($del->rowCount() > 0) {
        db()->prepare('UPDATE communities SET member_count = GREATEST(0, member_count - 1) WHERE id = ?')->execute([$communityId]);
    }
}

function is_community_member(int $communityId, int $userId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM community_members WHERE community_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$communityId, $userId]);

    return (bool) $stmt->fetchColumn();
}

function follow_organizer(int $orgId, int $userId): void
{
    db()->prepare('INSERT IGNORE INTO organizer_followers (organization_id, follower_id) VALUES (?, ?)')
        ->execute([$orgId, $userId]);
}

function unfollow_organizer(int $orgId, int $userId): void
{
    db()->prepare('DELETE FROM organizer_followers WHERE organization_id = ? AND follower_id = ?')
        ->execute([$orgId, $userId]);
}

function is_following_organizer(int $orgId, int $userId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM organizer_followers WHERE organization_id = ? AND follower_id = ? LIMIT 1');
    $stmt->execute([$orgId, $userId]);

    return (bool) $stmt->fetchColumn();
}

function org_follower_count(int $orgId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM organizer_followers WHERE organization_id = ?');
    $stmt->execute([$orgId]);

    return (int) $stmt->fetchColumn();
}

function featured_organizers(int $limit = 6): array
{
    $stmt = db()->prepare(
        'SELECT o.*, u.full_name AS owner_name FROM organizations o
         JOIN users u ON u.id = o.owner_id
         WHERE o.is_featured = 1 AND o.status = ? AND o.profile_public = 1
         ORDER BY o.rating_avg DESC, o.rating_count DESC LIMIT ?'
    );
    $stmt->bindValue(1, 'active', PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function public_organizer_profile(int $orgId): ?array
{
    $stmt = db()->prepare(
        'SELECT o.*, u.full_name AS owner_name, u.avatar AS owner_avatar
         FROM organizations o JOIN users u ON u.id = o.owner_id
         WHERE o.id = ? AND o.profile_public = 1 AND o.status = ? LIMIT 1'
    );
    $stmt->execute([$orgId, 'active']);

    return $stmt->fetch() ?: null;
}

function user_communities(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT c.*, o.name AS org_name FROM community_members cm
         JOIN communities c ON c.id = cm.community_id
         JOIN organizations o ON o.id = c.organization_id
         WHERE cm.user_id = ? ORDER BY cm.joined_at DESC'
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function create_recurring_series(int $orgId, string $title, string $frequency, ?int $templateEventId = null): int
{
    db()->prepare(
        'INSERT INTO recurring_event_series (organization_id, title, template_event_id, frequency, next_date)
         VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY))'
    )->execute([$orgId, $title, $templateEventId, $frequency]);

    return (int) db()->lastInsertId();
}

function get_org_recurring_series(int $orgId): array
{
    $stmt = db()->prepare('SELECT * FROM recurring_event_series WHERE organization_id = ? ORDER BY created_at DESC');
    $stmt->execute([$orgId]);

    return $stmt->fetchAll();
}
