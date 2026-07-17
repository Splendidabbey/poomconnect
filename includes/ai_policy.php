<?php

declare(strict_types=1);

function ensure_ai_policy_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS ai_usage_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            action VARCHAR(60) NOT NULL,
            event_id INT UNSIGNED NULL,
            meta JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_ai_usage_org (organization_id),
            INDEX idx_ai_usage_date (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ready = true;
}

function log_ai_usage(string $action, ?int $orgId = null, ?int $userId = null, ?int $eventId = null, array $meta = []): void
{
    db()->prepare(
        'INSERT INTO ai_usage_logs (organization_id, user_id, action, event_id, meta) VALUES (?, ?, ?, ?, ?)'
    )->execute([$orgId, $userId, $action, $eventId, json_encode($meta) ?: null]);
}

function ai_sensitive_fields(): array
{
    return ['gender', 'date_of_birth', 'personality', 'relationship_goal'];
}

function ai_sanitize_profile_for_matching(?array $data): ?array
{
    if (!$data) {
        return null;
    }

    foreach (ai_sensitive_fields() as $field) {
        unset($data[$field], $data['user_' . $field]);
    }

    return $data;
}

function ai_ethical_compatibility_score(int $userAId, int $userBId): int
{
    if (users_are_blocked($userAId, $userBId)) {
        return 0;
    }

    $a = ai_sanitize_profile_for_matching(get_compatibility_data($userAId));
    $b = ai_sanitize_profile_for_matching(get_compatibility_data($userBId));
    if (!$a || !$b) {
        return 50;
    }

    $score = 45;

    $interestA = $a['interests'] ?? [];
    $interestB = $b['interests'] ?? [];
    if ($interestA && $interestB) {
        $overlap = count(array_intersect(array_map('strtolower', $interestA), array_map('strtolower', $interestB)));
        $score += min(25, $overlap * 8);
    }

    if (!empty($a['communication_style']) && ($a['communication_style'] === ($b['communication_style'] ?? ''))) {
        $score += 10;
    }

    if (!empty($a['networking_goal']) && $a['networking_goal'] === ($b['networking_goal'] ?? '')) {
        $score += 10;
    }

    if (!empty($a['city']) && strtolower($a['city']) === strtolower($b['city'] ?? '')) {
        $score += 5;
    }

    return min(95, max(15, $score));
}

function ai_friendly_match_explanation(int $score): string
{
    if ($score >= 80) {
        return __('ai_policy.explanation_high');
    }
    if ($score >= 60) {
        return __('ai_policy.explanation_good');
    }

    return __('ai_policy.explanation_friendly');
}

function ai_usage_stats(?int $days = 30): array
{
    $stmt = db()->prepare(
        "SELECT action, COUNT(*) AS cnt FROM ai_usage_logs
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
         GROUP BY action ORDER BY cnt DESC"
    );
    $stmt->execute([$days]);

    $total = db()->prepare(
        'SELECT COUNT(*) FROM ai_usage_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
    );
    $total->execute([$days]);

    return [
        'total' => (int) $total->fetchColumn(),
        'by_action' => $stmt->fetchAll(),
        'days' => $days,
    ];
}

function ai_usage_by_org(int $limit = 20): array
{
    $stmt = db()->prepare(
        'SELECT o.name, COUNT(l.id) AS usage_count FROM ai_usage_logs l
         JOIN organizations o ON o.id = l.organization_id
         GROUP BY l.organization_id ORDER BY usage_count DESC LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
