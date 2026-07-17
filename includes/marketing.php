<?php

declare(strict_types=1);

function ensure_marketing_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS email_campaigns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            body_html TEXT NOT NULL,
            body_text TEXT NULL,
            audience ENUM('all_participants','event_attendees','followers','custom') NOT NULL DEFAULT 'all_participants',
            event_id INT UNSIGNED NULL,
            status ENUM('draft','scheduled','sent','cancelled') NOT NULL DEFAULT 'draft',
            scheduled_at TIMESTAMP NULL,
            sent_at TIMESTAMP NULL,
            sent_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS email_campaign_recipients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            email VARCHAR(180) NOT NULL,
            status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
            sent_at TIMESTAMP NULL,
            FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_campaign_recipient (campaign_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS social_share_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            entity_type ENUM('event','blog','org','referral') NOT NULL,
            entity_id INT UNSIGNED NOT NULL,
            channel VARCHAR(30) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_share_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $orgCols = [
        'tiktok_handle' => 'VARCHAR(80) NULL AFTER landing_cta',
        'seo_keywords' => 'VARCHAR(255) NULL AFTER tiktok_handle',
    ];
    foreach ($orgCols as $col => $def) {
        if (!table_has_column('organizations', $col)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN {$col} {$def}");
        }
    }

    $ready = true;
}

function get_org_email_campaigns(int $orgId): array
{
    $stmt = db()->prepare('SELECT * FROM email_campaigns WHERE organization_id = ? ORDER BY created_at DESC');
    $stmt->execute([$orgId]);

    return $stmt->fetchAll();
}

function create_email_campaign(int $orgId, int $userId, array $data): int
{
    db()->prepare(
        'INSERT INTO email_campaigns (organization_id, name, subject, body_html, body_text, audience, event_id, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $orgId,
        $data['name'],
        $data['subject'],
        $data['body_html'],
        $data['body_text'] ?? strip_tags($data['body_html']),
        $data['audience'] ?? 'all_participants',
        $data['event_id'] ?: null,
        'draft',
        $userId,
    ]);

    return (int) db()->lastInsertId();
}

function send_email_campaign(int $campaignId, int $orgId): array
{
    $stmt = db()->prepare('SELECT * FROM email_campaigns WHERE id = ? AND organization_id = ? LIMIT 1');
    $stmt->execute([$campaignId, $orgId]);
    $campaign = $stmt->fetch();
    if (!$campaign || $campaign['status'] === 'sent') {
        return ['ok' => false, 'error' => __('marketing.campaign_not_found')];
    }

    $recipients = campaign_audience_users($orgId, $campaign['audience'], (int) ($campaign['event_id'] ?? 0));
    $sent = 0;

    foreach ($recipients as $user) {
        $body = $campaign['body_html'];
        $subject = $campaign['subject'];
        @mail($user['email'], $subject . ' | ' . app_name(), strip_tags($body) . "\n\n— " . app_name());

        db()->prepare(
            'INSERT INTO email_campaign_recipients (campaign_id, user_id, email, status, sent_at) VALUES (?, ?, ?, ?, NOW())'
        )->execute([$campaignId, $user['id'], $user['email'], 'sent']);
        ++$sent;
    }

    db()->prepare("UPDATE email_campaigns SET status = 'sent', sent_at = NOW(), sent_count = ? WHERE id = ?")
        ->execute([$sent, $campaignId]);

    return ['ok' => true, 'sent' => $sent];
}

function campaign_audience_users(int $orgId, string $audience, int $eventId = 0): array
{
    if ($audience === 'event_attendees' && $eventId) {
        $stmt = db()->prepare(
            "SELECT DISTINCT u.id, u.email FROM event_participants ep
             JOIN users u ON u.id = ep.user_id
             JOIN events e ON e.id = ep.event_id
             WHERE e.organization_id = ? AND ep.event_id = ? AND ep.registration_status = 'registered'"
        );
        $stmt->execute([$orgId, $eventId]);
    } elseif ($audience === 'followers') {
        $stmt = db()->prepare(
            'SELECT u.id, u.email FROM organizer_followers f
             JOIN users u ON u.id = f.follower_id
             WHERE f.organization_id = ?'
        );
        $stmt->execute([$orgId]);
    } else {
        $stmt = db()->prepare(
            "SELECT DISTINCT u.id, u.email FROM event_participants ep
             JOIN users u ON u.id = ep.user_id
             JOIN events e ON e.id = ep.event_id
             WHERE e.organization_id = ? AND ep.registration_status = 'registered'"
        );
        $stmt->execute([$orgId]);
    }

    return $stmt->fetchAll();
}

function social_share_channels(): array
{
    return ['copy', 'facebook', 'x', 'line', 'tiktok', 'whatsapp'];
}

function social_share_url(string $channel, string $url, string $title, ?string $text = null): string
{
    $encodedUrl = rawurlencode($url);
    $encodedTitle = rawurlencode($title);
    $encodedText = rawurlencode($text ?? $title);

    return match ($channel) {
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl,
        'x' => 'https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedText,
        'line' => 'https://social-plugins.line.me/lineit/share?url=' . $encodedUrl,
        'whatsapp' => 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url),
        'tiktok' => 'https://www.tiktok.com/upload?lang=' . rawurlencode(current_locale()) . '&share_url=' . $encodedUrl,
        default => $url,
    };
}

function log_social_share(string $entityType, int $entityId, string $channel, ?int $userId = null): void
{
    db()->prepare('INSERT INTO social_share_logs (user_id, entity_type, entity_id, channel) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $entityType, $entityId, $channel]);
}

function marketing_stats(int $orgId): array
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM email_campaigns WHERE organization_id = ?');
    $stmt->execute([$orgId]);
    $campaignCount = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('SELECT COALESCE(SUM(sent_count), 0) FROM email_campaigns WHERE organization_id = ?');
    $stmt->execute([$orgId]);
    $emailsSent = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM social_share_logs s
         JOIN events e ON s.entity_type = \'event\' AND s.entity_id = e.id
         WHERE e.organization_id = ?'
    );
    $stmt->execute([$orgId]);
    $shares = (int) $stmt->fetchColumn();

    $cStmt = db()->prepare('SELECT COUNT(*) FROM coupons WHERE organization_id = ?');
    $cStmt->execute([$orgId]);
    $couponCount = (int) $cStmt->fetchColumn();

    $refStmt = db()->prepare('SELECT uses_count FROM referral_codes WHERE organization_id = ? LIMIT 1');
    $refStmt->execute([$orgId]);
    $referralUses = (int) ($refStmt->fetchColumn() ?: 0);

    return [
        'campaigns' => $campaignCount,
        'emails_sent' => $emailsSent,
        'social_shares' => $shares,
        'coupons' => $couponCount,
        'referral_uses' => $referralUses,
    ];
}

function tiktok_share_caption(string $title, string $url, ?string $handle = null): string
{
    $tags = '#PoomConnect #Events #Networking';
    if ($handle) {
        $tags .= ' @' . ltrim($handle, '@');
    }

    return $title . ' ' . $url . ' ' . $tags;
}
