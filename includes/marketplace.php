<?php

declare(strict_types=1);

function ensure_marketplace_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS host_applications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            organization_name VARCHAR(150) NOT NULL,
            event_types JSON NULL,
            experience TEXT NULL,
            website VARCHAR(255) NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            reviewed_by INT UNSIGNED NULL,
            review_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_host_app_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS organizer_ratings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            event_id INT UNSIGNED NULL,
            rating TINYINT UNSIGNED NOT NULL,
            review TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_org_rating (organization_id, user_id, event_id),
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
            CHECK (rating BETWEEN 1 AND 5)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ready = true;
}

function submit_host_application(int $userId, string $orgName, array $eventTypes, string $experience, ?string $website): int
{
    db()->prepare(
        'INSERT INTO host_applications (user_id, organization_name, event_types, experience, website) VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, $orgName, json_encode($eventTypes), $experience ?: null, $website ?: null]);

    return (int) db()->lastInsertId();
}

function pending_host_applications(int $limit = 50): array
{
    $stmt = db()->prepare(
        'SELECT ha.*, u.full_name, u.email FROM host_applications ha
         JOIN users u ON u.id = ha.user_id WHERE ha.status = ? ORDER BY ha.created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, 'pending', PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function review_host_application(int $appId, string $status, int $reviewerId, ?string $notes = null): void
{
    db()->prepare('UPDATE host_applications SET status = ?, reviewed_by = ?, review_notes = ? WHERE id = ?')
        ->execute([$status, $reviewerId, $notes, $appId]);

    if ($status === 'approved') {
        $app = db()->prepare('SELECT * FROM host_applications WHERE id = ? LIMIT 1');
        $app->execute([$appId]);
        $row = $app->fetch();
        if ($row) {
            db()->prepare("UPDATE users SET role = 'organizer' WHERE id = ? AND role = 'participant'")
                ->execute([(int) $row['user_id']]);
        }
    }
}

function rate_organizer(int $orgId, int $userId, int $rating, ?string $review = null, ?int $eventId = null): void
{
    $rating = max(1, min(5, $rating));
    db()->prepare(
        'INSERT INTO organizer_ratings (organization_id, user_id, event_id, rating, review)
         VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review)'
    )->execute([$orgId, $userId, $eventId, $rating, $review ?: null]);

    $avg = db()->prepare('SELECT AVG(rating), COUNT(*) FROM organizer_ratings WHERE organization_id = ?');
    $avg->execute([$orgId]);
    [$avgRating, $count] = $avg->fetch(PDO::FETCH_NUM);
    db()->prepare('UPDATE organizations SET rating_avg = ?, rating_count = ? WHERE id = ?')
        ->execute([round((float) $avgRating, 2), (int) $count, $orgId]);
}

function org_ratings(int $orgId, int $limit = 10): array
{
    $stmt = db()->prepare(
        'SELECT r.*, u.full_name FROM organizer_ratings r
         JOIN users u ON u.id = r.user_id WHERE r.organization_id = ? ORDER BY r.created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, $orgId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function user_host_application(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM host_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);

    return $stmt->fetch() ?: null;
}

function set_org_featured(int $orgId, bool $featured): void
{
    db()->prepare('UPDATE organizations SET is_featured = ? WHERE id = ?')->execute([$featured ? 1 : 0, $orgId]);
}

function marketplace_organizers(int $limit = 12): array
{
    $stmt = db()->prepare(
        'SELECT o.*, u.full_name AS owner_name FROM organizations o
         JOIN users u ON u.id = o.owner_id
         WHERE o.status = ? AND o.profile_public = 1
         ORDER BY o.is_featured DESC, o.rating_avg DESC, o.rating_count DESC LIMIT ?'
    );
    $stmt->bindValue(1, 'active', PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
