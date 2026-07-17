<?php

declare(strict_types=1);

function ensure_safety_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_reports (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT UNSIGNED NOT NULL,
            reported_id INT UNSIGNED NOT NULL,
            event_id INT UNSIGNED NULL,
            reason ENUM('harassment','spam','inappropriate','fake_profile','other') NOT NULL DEFAULT 'other',
            details TEXT NULL,
            status ENUM('pending','reviewed','dismissed','actioned') NOT NULL DEFAULT 'pending',
            reviewed_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
            INDEX idx_reports_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_blocks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            blocker_id INT UNSIGNED NOT NULL,
            blocked_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_block (blocker_id, blocked_id),
            FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ready = true;
}

function report_user(int $reporterId, int $reportedId, string $reason, ?string $details = null, ?int $eventId = null): bool
{
    if ($reporterId === $reportedId) {
        return false;
    }

    db()->prepare(
        'INSERT INTO user_reports (reporter_id, reported_id, event_id, reason, details) VALUES (?, ?, ?, ?, ?)'
    )->execute([$reporterId, $reportedId, $eventId, $reason, trim($details ?? '') ?: null]);

    notify_moderators_new_report($reporterId, $reportedId, $reason);

    return true;
}

function block_user(int $blockerId, int $blockedId): bool
{
    if ($blockerId === $blockedId) {
        return false;
    }

    db()->prepare('INSERT IGNORE INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)')
        ->execute([$blockerId, $blockedId]);

    return true;
}

function unblock_user(int $blockerId, int $blockedId): void
{
    db()->prepare('DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?')
        ->execute([$blockerId, $blockedId]);
}

function users_are_blocked(int $userA, int $userB): bool
{
    $stmt = db()->prepare(
        'SELECT 1 FROM user_blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1'
    );
    $stmt->execute([$userA, $userB, $userB, $userA]);

    return (bool) $stmt->fetchColumn();
}

function get_blocked_user_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT blocked_id FROM user_blocks WHERE blocker_id = ?');
    $stmt->execute([$userId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function save_emergency_contact(int $userId, string $name, string $phone): void
{
    db()->prepare('UPDATE users SET emergency_contact_name = ?, emergency_contact_phone = ? WHERE id = ?')
        ->execute([trim($name) ?: null, trim($phone) ?: null, $userId]);
}

function org_has_safe_badge(int $orgId): bool
{
    $stmt = db()->prepare('SELECT safe_event_badge FROM organizations WHERE id = ? LIMIT 1');
    $stmt->execute([$orgId]);

    return (bool) $stmt->fetchColumn();
}

function set_org_safe_badge(int $orgId, bool $enabled): void
{
    db()->prepare('UPDATE organizations SET safe_event_badge = ? WHERE id = ?')
        ->execute([$enabled ? 1 : 0, $orgId]);
}

function pending_reports(int $limit = 50): array
{
    $stmt = db()->prepare(
        'SELECT r.*, ur.full_name AS reporter_name, ud.full_name AS reported_name
         FROM user_reports r
         JOIN users ur ON ur.id = r.reporter_id
         JOIN users ud ON ud.id = r.reported_id
         WHERE r.status = ?
         ORDER BY r.created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, 'pending', PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function update_report_status(int $reportId, string $status, int $reviewerId): void
{
    db()->prepare('UPDATE user_reports SET status = ?, reviewed_by = ? WHERE id = ?')
        ->execute([$status, $reviewerId, $reportId]);
}

function notify_moderators_new_report(int $reporterId, int $reportedId, string $reason): void
{
    $mods = db()->query("SELECT id FROM users WHERE role IN ('moderator','admin','super_admin')")->fetchAll();
    foreach ($mods as $mod) {
        notify_user((int) $mod['id'], 'moderation', __('safety.report_received'), __('safety.report_received_body', [
            'reason' => $reason,
        ]), ['reporter_id' => $reporterId, 'reported_id' => $reportedId], ['in_app']);
    }
}

function safety_report_reasons(): array
{
    return ['harassment', 'spam', 'inappropriate', 'fake_profile', 'other'];
}
