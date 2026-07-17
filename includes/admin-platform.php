<?php

declare(strict_types=1);

function ensure_admin_platform_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS support_tickets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            organization_id INT UNSIGNED NULL,
            subject VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
            status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
            assigned_to INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
            INDEX idx_ticket_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS platform_countries (
            code CHAR(2) PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    seed_platform_countries();
    $ready = true;
}

function seed_platform_countries(): void
{
    $countries = [
        ['TH', 'Thailand'],
        ['US', 'United States'],
        ['GB', 'United Kingdom'],
        ['SG', 'Singapore'],
        ['JP', 'Japan'],
        ['AU', 'Australia'],
        ['DE', 'Germany'],
        ['FR', 'France'],
    ];

    $stmt = db()->prepare('INSERT IGNORE INTO platform_countries (code, name) VALUES (?, ?)');
    foreach ($countries as [$code, $name]) {
        $stmt->execute([$code, $name]);
    }
}

function create_support_ticket(int $userId, string $subject, string $body, ?int $orgId = null, string $priority = 'normal'): int
{
    db()->prepare(
        'INSERT INTO support_tickets (user_id, organization_id, subject, body, priority) VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, $orgId, $subject, $body, $priority]);

    return (int) db()->lastInsertId();
}

function get_support_tickets(?string $status = null, int $limit = 50): array
{
    if ($status) {
        $stmt = db()->prepare(
            'SELECT t.*, u.full_name, u.email, o.name AS org_name FROM support_tickets t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN organizations o ON o.id = t.organization_id
             WHERE t.status = ? ORDER BY t.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $status, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    } else {
        $stmt = db()->prepare(
            'SELECT t.*, u.full_name, u.email, o.name AS org_name FROM support_tickets t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN organizations o ON o.id = t.organization_id
             ORDER BY t.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function update_ticket_status(int $ticketId, string $status, ?int $assignedTo = null): void
{
    db()->prepare('UPDATE support_tickets SET status = ?, assigned_to = COALESCE(?, assigned_to) WHERE id = ?')
        ->execute([$status, $assignedTo, $ticketId]);
}

function get_system_logs(int $limit = 100): array
{
    $stmt = db()->prepare(
        'SELECT l.*, u.full_name FROM admin_logs l
         JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function platform_countries(): array
{
    return db()->query('SELECT * FROM platform_countries WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
}

function platform_revenue_report(): array
{
    $total = db()->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'approved'")->fetchColumn();
    $month = db()->query(
        "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'approved'
         AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
    $byOrg = db()->query(
        "SELECT o.name, COALESCE(SUM(p.amount), 0) AS revenue FROM organizations o
         LEFT JOIN events e ON e.organization_id = o.id
         LEFT JOIN payments p ON p.event_id = e.id AND p.payment_status = 'approved'
         GROUP BY o.id ORDER BY revenue DESC LIMIT 20"
    )->fetchAll();

    return ['total' => (float) $total, 'month' => (float) $month, 'by_org' => $byOrg];
}

function platform_org_stats(): array
{
    return [
        'total' => (int) db()->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
        'active' => (int) db()->query("SELECT COUNT(*) FROM organizations WHERE status = 'active'")->fetchColumn(),
        'suspended' => (int) db()->query("SELECT COUNT(*) FROM organizations WHERE status = 'suspended'")->fetchColumn(),
        'featured' => (int) db()->query('SELECT COUNT(*) FROM organizations WHERE is_featured = 1')->fetchColumn(),
    ];
}

function suspend_organization(int $orgId): void
{
    db()->prepare("UPDATE organizations SET status = 'suspended' WHERE id = ?")->execute([$orgId]);
}

function activate_organization(int $orgId): void
{
    db()->prepare("UPDATE organizations SET status = 'active' WHERE id = ?")->execute([$orgId]);
}

function admin_extended_stats(): array
{
    $subs = platform_subscription_stats();
    $reports = (int) db()->query("SELECT COUNT(*) FROM user_reports WHERE status = 'pending'")->fetchColumn();
    $tickets = (int) db()->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress')")->fetchColumn();
    $ai = ai_usage_stats(30);

    return [
        'subscriptions' => $subs,
        'pending_reports' => $reports,
        'open_tickets' => $tickets,
        'ai_usage' => $ai,
        'orgs' => platform_org_stats(),
    ];
}
