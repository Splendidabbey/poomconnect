<?php

declare(strict_types=1);

function ensure_subscription_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(40) NOT NULL UNIQUE,
            name VARCHAR(80) NOT NULL,
            price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            features JSON NOT NULL,
            max_events INT UNSIGNED NULL,
            max_participants INT UNSIGNED NULL,
            white_label TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS organization_subscriptions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            plan_id INT UNSIGNED NOT NULL,
            status ENUM('active','trialing','past_due','cancelled') NOT NULL DEFAULT 'active',
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT,
            INDEX idx_org_sub (organization_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    seed_subscription_plans();
    $ready = true;
}

function seed_subscription_plans(): void
{
    $plans = [
        ['starter', 'Starter', 0, ['events' => 3, 'analytics' => true, 'payments' => true], 3, 50, 0, 1],
        ['pro', 'Pro', 990, ['events' => 20, 'analytics' => true, 'payments' => true, 'coupons' => true, 'referrals' => true], 20, 200, 0, 2],
        ['business', 'Business', 2990, ['events' => 100, 'analytics' => true, 'payments' => true, 'coupons' => true, 'referrals' => true, 'ai_matching' => true, 'community' => true], 100, 500, 0, 3],
        ['enterprise', 'Enterprise', 9990, ['events' => null, 'analytics' => true, 'payments' => true, 'coupons' => true, 'referrals' => true, 'ai_matching' => true, 'community' => true, 'priority_support' => true], null, null, 0, 4],
        ['white_label', 'White Label', 19990, ['events' => null, 'analytics' => true, 'payments' => true, 'coupons' => true, 'referrals' => true, 'ai_matching' => true, 'community' => true, 'white_label' => true, 'custom_domain' => true], null, null, 1, 5],
    ];

    $stmt = db()->prepare(
        'INSERT IGNORE INTO subscription_plans (slug, name, price_monthly, features, max_events, max_participants, white_label, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($plans as [$slug, $name, $price, $features, $maxEvents, $maxParticipants, $whiteLabel, $sort]) {
        $stmt->execute([$slug, $name, $price, json_encode($features), $maxEvents, $maxParticipants, $whiteLabel, $sort]);
    }
}

function get_subscription_plans(): array
{
    return db()->query('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();
}

function get_plan_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM subscription_plans WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);

    return $stmt->fetch() ?: null;
}

function get_org_subscription(int $orgId): ?array
{
    $stmt = db()->prepare(
        'SELECT os.*, sp.slug AS plan_slug, sp.name AS plan_name, sp.features, sp.max_events, sp.max_participants, sp.white_label
         FROM organization_subscriptions os
         JOIN subscription_plans sp ON sp.id = os.plan_id
         WHERE os.organization_id = ? AND os.status IN (\'active\',\'trialing\')
         ORDER BY os.started_at DESC LIMIT 1'
    );
    $stmt->execute([$orgId]);

    return $stmt->fetch() ?: null;
}

function assign_org_plan(int $orgId, string $planSlug): void
{
    $plan = get_plan_by_slug($planSlug);
    if (!$plan) {
        return;
    }

    db()->prepare("UPDATE organization_subscriptions SET status = 'cancelled' WHERE organization_id = ? AND status IN ('active','trialing')")
        ->execute([$orgId]);

    db()->prepare(
        'INSERT INTO organization_subscriptions (organization_id, plan_id, status, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 MONTH))'
    )->execute([$orgId, (int) $plan['id'], 'active']);

    db()->prepare('UPDATE organizations SET subscription_plan_id = ? WHERE id = ?')
        ->execute([(int) $plan['id'], $orgId]);
}

function ensure_org_subscription(int $orgId): void
{
    if (get_org_subscription($orgId)) {
        return;
    }
    assign_org_plan($orgId, 'starter');
}

function org_plan_features(int $orgId): array
{
    $sub = get_org_subscription($orgId);
    if (!$sub) {
        return json_decode(get_plan_by_slug('starter')['features'] ?? '{}', true) ?: [];
    }

    return json_decode($sub['features'] ?? '{}', true) ?: [];
}

function org_has_feature(int $orgId, string $feature): bool
{
    $features = org_plan_features($orgId);

    return !empty($features[$feature]);
}

function org_within_event_limit(int $orgId): bool
{
    $sub = get_org_subscription($orgId);
    if (!$sub || $sub['max_events'] === null) {
        return true;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM events WHERE organization_id = ?');
    $stmt->execute([$orgId]);

    return (int) $stmt->fetchColumn() < (int) $sub['max_events'];
}

function platform_subscription_stats(): array
{
    $plans = db()->query(
        "SELECT sp.name, sp.slug, COUNT(os.id) AS org_count,
                SUM(sp.price_monthly) AS mrr
         FROM subscription_plans sp
         LEFT JOIN organization_subscriptions os ON os.plan_id = sp.id AND os.status IN ('active','trialing')
         GROUP BY sp.id ORDER BY sp.sort_order"
    )->fetchAll();

    $mrr = db()->query(
        "SELECT COALESCE(SUM(sp.price_monthly), 0) FROM organization_subscriptions os
         JOIN subscription_plans sp ON sp.id = os.plan_id WHERE os.status IN ('active','trialing')"
    )->fetchColumn();

    return ['plans' => $plans, 'mrr' => (float) $mrr];
}
