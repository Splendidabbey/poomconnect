<?php

declare(strict_types=1);

function ensure_realtime_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $liveCols = [
        'broadcast_message' => 'TEXT NULL AFTER timer_seconds',
        'timer_started_at' => 'TIMESTAMP NULL AFTER broadcast_message',
        'emergency_stopped' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER timer_started_at',
    ];
    foreach ($liveCols as $col => $def) {
        if (!table_has_column('live_event_state', $col)) {
            $pdo->exec("ALTER TABLE live_event_state ADD COLUMN {$col} {$def}");
        }
    }

    if (!table_has_column('matches', 'match_type')) {
        $pdo->exec("ALTER TABLE matches ADD COLUMN match_type ENUM('like','friend','business') NOT NULL DEFAULT 'like' AFTER user_b");
        try {
            $pdo->exec('ALTER TABLE matches DROP INDEX unique_match');
        } catch (PDOException) {
            // index may differ
        }
        try {
            $pdo->exec('ALTER TABLE matches ADD UNIQUE KEY unique_event_match (event_id, user_a, user_b, match_type)');
        } catch (PDOException) {
            // may exist
        }
    }

    if (!table_has_column('users', 'loyalty_points')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN loyalty_points INT UNSIGNED NOT NULL DEFAULT 0 AFTER privacy_settings');
        $pdo->exec('ALTER TABLE users ADD COLUMN loyalty_level VARCHAR(30) NOT NULL DEFAULT \'bronze\' AFTER loyalty_points');
        $pdo->exec('ALTER TABLE users ADD COLUMN referral_credits INT UNSIGNED NOT NULL DEFAULT 0 AFTER loyalty_level');
        $pdo->exec('ALTER TABLE users ADD COLUMN is_vip TINYINT(1) NOT NULL DEFAULT 0 AFTER referral_credits');
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_broadcasts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_broadcast_event (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chat_rooms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            user_a INT UNSIGNED NOT NULL,
            user_b INT UNSIGNED NOT NULL,
            unlocked_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_chat (event_id, user_a, user_b),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_a) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user_b) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            room_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            body TEXT NULL,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_chat_room (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(200) NOT NULL,
            body TEXT NULL,
            channel ENUM('in_app','email','line','push','sms') NOT NULL DEFAULT 'in_app',
            meta JSON NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_notifications_user (user_id),
            INDEX idx_notifications_unread (user_id, read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_badges (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            badge_key VARCHAR(50) NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_badge (user_id, badge_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS referral_uses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            referral_code_id INT UNSIGNED NOT NULL,
            referred_user_id INT UNSIGNED NOT NULL,
            credits_awarded INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE CASCADE,
            FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_reminder_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            reminded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_event_reminder (event_id, user_id),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_reminder_event (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ready = true;
}

function live_timer_remaining(array $liveState): int
{
    $total = (int) ($liveState['timer_seconds'] ?? 0);
    if (empty($liveState['timer_started_at']) || ($liveState['event_status'] ?? '') !== 'live') {
        return $total;
    }
    $elapsed = time() - strtotime($liveState['timer_started_at']);
    return max(0, $total - $elapsed);
}

function reset_live_timer(int $eventId, int $seconds): void
{
    db()->prepare(
        'UPDATE live_event_state SET timer_seconds = ?, timer_started_at = NOW(), updated_at = NOW() WHERE event_id = ?'
    )->execute([$seconds, $eventId]);
}

function send_event_broadcast(int $eventId, int $senderId, string $message): void
{
    $message = trim($message);
    if ($message === '') {
        return;
    }

    db()->prepare('INSERT INTO event_broadcasts (event_id, sender_id, message) VALUES (?, ?, ?)')
        ->execute([$eventId, $senderId, $message]);

    db()->prepare('UPDATE live_event_state SET broadcast_message = ?, updated_at = NOW() WHERE event_id = ?')
        ->execute([$message, $eventId]);

    $participants = db()->prepare(
        "SELECT user_id FROM event_participants WHERE event_id = ? AND registration_status = 'registered' AND payment_status = 'approved'"
    );
    $participants->execute([$eventId]);

    foreach ($participants->fetchAll() as $row) {
        notify_user((int) $row['user_id'], 'organizer_announcement', __('notify.announcement_title'), $message, [
            'event_id' => $eventId,
        ], ['in_app', 'email']);
    }
}

function emergency_stop_event(int $eventId): void
{
    db()->prepare(
        "UPDATE live_event_state SET event_status = 'ended', emergency_stopped = 1, updated_at = NOW() WHERE event_id = ?"
    )->execute([$eventId]);
    db()->prepare("UPDATE events SET status = 'completed' WHERE id = ?")->execute([$eventId]);
}

function resume_live_event(int $eventId): void
{
    db()->prepare(
        "UPDATE live_event_state SET event_status = 'live', timer_started_at = NOW(), updated_at = NOW() WHERE event_id = ?"
    )->execute([$eventId]);
    db()->prepare("UPDATE events SET status = 'live' WHERE id = ?")->execute([$eventId]);
}

function get_live_state_payload(int $eventId, int $userId): array
{
    $live = get_live_state($eventId);
    if (!$live) {
        return ['status' => 'waiting', 'round' => 0, 'timer' => 0, 'broadcast' => null];
    }

    $payload = [
        'status' => $live['event_status'],
        'round' => (int) $live['current_round'],
        'timer' => live_timer_remaining($live),
        'timer_total' => (int) $live['timer_seconds'],
        'broadcast' => $live['broadcast_message'] ?? null,
        'emergency' => (bool) ($live['emergency_stopped'] ?? false),
        'partner' => null,
        'starters' => [],
        'compatibility' => 0,
        'round_id' => null,
    ];

    if ($payload['round'] > 0 && $payload['status'] === 'live') {
        $stmt = db()->prepare(
            'SELECT * FROM rounds WHERE event_id = ? AND round_number = ? AND (participant_a = ? OR participant_b = ?) ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$eventId, $payload['round'], $userId, $userId]);
        $pairing = $stmt->fetch();
        if ($pairing) {
            $partnerId = (int) $pairing['participant_a'] === $userId
                ? (int) $pairing['participant_b']
                : (int) $pairing['participant_a'];
            $partner = get_user_profile($partnerId);
            $payload['round_id'] = (int) $pairing['id'];
            $payload['compatibility'] = calculate_compatibility($userId, $partnerId);
            $payload['starters'] = conversation_suggestions($userId, $partnerId);
            $payload['partner'] = [
                'id' => $partnerId,
                'name' => $partner['full_name'] ?? '',
                'avatar' => !empty($partner['avatar']) ? upload_url($partner['avatar']) : default_avatar($partner['full_name'] ?? ''),
                'occupation' => $partner['occupation'] ?? '',
            ];
        }
    }

    return $payload;
}

function record_mutual_match(int $eventId, int $userA, int $userB, string $matchType): void
{
    $a = min($userA, $userB);
    $b = max($userA, $userB);

    $stmt = db()->prepare(
        'INSERT IGNORE INTO matches (event_id, user_a, user_b, match_type) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$eventId, $a, $b, $matchType]);

    if ($stmt->rowCount() > 0) {
        unlock_chat_room($eventId, $a, $b);
        award_loyalty_points($userA, 50, 'match');
        award_loyalty_points($userB, 50, 'match');
        award_badge($userA, 'first_match');
        award_badge($userB, 'first_match');

        $event = get_event_by_id($eventId);
        $title = __('notify.match_found_title');
        $body = __('notify.match_found_body', ['event' => $event['title'] ?? 'Event']);
        notify_user($userA, 'match_found', $title, $body, ['event_id' => $eventId, 'match_type' => $matchType], ['in_app', 'email']);
        notify_user($userB, 'match_found', $title, $body, ['event_id' => $eventId, 'match_type' => $matchType], ['in_app', 'email']);
    }
}

function get_user_event_matches(int $userId, int $eventId, ?string $type = null): array
{
    $sql = 'SELECT m.*, CASE WHEN m.user_a = ? THEN ub.full_name ELSE ua.full_name END AS partner_name,
                   CASE WHEN m.user_a = ? THEN ub.avatar ELSE ua.avatar END AS partner_avatar,
                   CASE WHEN m.user_a = ? THEN m.user_b ELSE m.user_a END AS partner_id
            FROM matches m
            JOIN users ua ON ua.id = m.user_a
            JOIN users ub ON ub.id = m.user_b
            WHERE m.event_id = ? AND (m.user_a = ? OR m.user_b = ?)';
    $params = [$userId, $userId, $userId, $eventId, $userId, $userId];

    if ($type) {
        $sql .= ' AND m.match_type = ?';
        $params[] = $type;
    }

    $sql .= ' ORDER BY m.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function get_user_all_matches(int $userId, ?string $type = null): array
{
    $sql = 'SELECT m.*, e.title AS event_title,
                   CASE WHEN m.user_a = ? THEN ub.full_name ELSE ua.full_name END AS partner_name,
                   CASE WHEN m.user_a = ? THEN ub.avatar ELSE ua.avatar END AS partner_avatar,
                   CASE WHEN m.user_a = ? THEN m.user_b ELSE m.user_a END AS partner_id
            FROM matches m
            JOIN events e ON e.id = m.event_id
            JOIN users ua ON ua.id = m.user_a
            JOIN users ub ON ub.id = m.user_b
            WHERE (m.user_a = ? OR m.user_b = ?)';
    $params = [$userId, $userId, $userId, $userId, $userId];

    if ($type) {
        $sql .= ' AND m.match_type = ?';
        $params[] = $type;
    }

    $sql .= ' ORDER BY m.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function unlock_chat_room(int $eventId, int $userA, int $userB): int
{
    $a = min($userA, $userB);
    $b = max($userA, $userB);

    $check = db()->prepare('SELECT id FROM chat_rooms WHERE event_id = ? AND user_a = ? AND user_b = ? LIMIT 1');
    $check->execute([$eventId, $a, $b]);
    $existing = $check->fetchColumn();
    if ($existing) {
        db()->prepare('UPDATE chat_rooms SET unlocked_at = COALESCE(unlocked_at, NOW()) WHERE id = ?')->execute([(int) $existing]);
        return (int) $existing;
    }

    db()->prepare('INSERT INTO chat_rooms (event_id, user_a, user_b, unlocked_at) VALUES (?, ?, ?, NOW())')
        ->execute([$eventId, $a, $b]);

    return (int) db()->lastInsertId();
}

function get_chat_room(int $roomId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM chat_rooms WHERE id = ? AND unlocked_at IS NOT NULL LIMIT 1');
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if (!$room || ((int) $room['user_a'] !== $userId && (int) $room['user_b'] !== $userId)) {
        return null;
    }

    return $room;
}

function get_chat_room_for_users(int $eventId, int $userId, int $otherId): ?array
{
    $a = min($userId, $otherId);
    $b = max($userId, $otherId);
    $stmt = db()->prepare('SELECT * FROM chat_rooms WHERE event_id = ? AND user_a = ? AND user_b = ? AND unlocked_at IS NOT NULL LIMIT 1');
    $stmt->execute([$eventId, $a, $b]);

    return $stmt->fetch() ?: null;
}

function get_user_chat_rooms(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT r.*, e.title AS event_title,
                CASE WHEN r.user_a = ? THEN ub.full_name ELSE ua.full_name END AS partner_name,
                CASE WHEN r.user_a = ? THEN r.user_b ELSE r.user_a END AS partner_id
         FROM chat_rooms r
         JOIN events e ON e.id = r.event_id
         JOIN users ua ON ua.id = r.user_a
         JOIN users ub ON ub.id = r.user_b
         WHERE r.unlocked_at IS NOT NULL AND (r.user_a = ? OR r.user_b = ?)
         ORDER BY r.unlocked_at DESC'
    );
    $stmt->execute([$userId, $userId, $userId, $userId]);

    return $stmt->fetchAll();
}

function get_chat_messages(int $roomId, int $limit = 100): array
{
    $stmt = db()->prepare(
        'SELECT m.*, u.full_name AS sender_name
         FROM chat_messages m JOIN users u ON u.id = m.sender_id
         WHERE m.room_id = ? ORDER BY m.created_at ASC LIMIT ?'
    );
    $stmt->bindValue(1, $roomId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function send_chat_message(int $roomId, int $senderId, string $body, ?string $imagePath = null): bool
{
    $room = get_chat_room($roomId, $senderId);
    if (!$room) {
        return false;
    }

    db()->prepare('INSERT INTO chat_messages (room_id, sender_id, body, image_path) VALUES (?, ?, ?, ?)')
        ->execute([$roomId, $senderId, trim($body) ?: null, $imagePath]);

    $otherId = (int) $room['user_a'] === $senderId ? (int) $room['user_b'] : (int) $room['user_a'];
    notify_user($otherId, 'chat_message', __('notify.chat_title'), __('notify.chat_body'), [
        'room_id' => $roomId,
    ], ['in_app']);

    return true;
}

function notify_user(int $userId, string $type, string $title, string $body, array $meta = [], array $channels = ['in_app']): void
{
    foreach ($channels as $channel) {
        db()->prepare(
            'INSERT INTO notifications (user_id, type, title, body, channel, meta) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $type, $title, $body, $channel, json_encode($meta)]);

        if ($channel === 'email') {
            send_notification_email($userId, $title, $body);
        }
        // LINE, push, SMS: logged for future integration
    }
}

function send_notification_email(int $userId, string $subject, string $body): void
{
    $stmt = db()->prepare('SELECT email, full_name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || empty($user['email'])) {
        return;
    }

    $message = $body . "\n\n— " . app_name();
    @mail($user['email'], $subject . ' | ' . app_name(), $message, 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'poomconnect.com'));
}

function get_user_notifications(int $userId, int $limit = 30): array
{
    $stmt = db()->prepare(
        "SELECT * FROM notifications WHERE user_id = ? AND channel = 'in_app' ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function unread_notification_count(int $userId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND channel = 'in_app' AND read_at IS NULL");
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

function mark_notifications_read(int $userId): void
{
    db()->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL")->execute([$userId]);
}

function award_loyalty_points(int $userId, int $points, string $reason = ''): void
{
    db()->prepare('UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?')->execute([$points, $userId]);

    $stmt = db()->prepare('SELECT loyalty_points FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $total = (int) $stmt->fetchColumn();

    $level = 'bronze';
    if ($total >= 500) {
        $level = 'gold';
    } elseif ($total >= 200) {
        $level = 'silver';
    }

    db()->prepare('UPDATE users SET loyalty_level = ? WHERE id = ?')->execute([$level, $userId]);

    if ($total >= 500) {
        db()->prepare('UPDATE users SET is_vip = 1 WHERE id = ?')->execute([$userId]);
        award_badge($userId, 'vip');
    }
}

function award_badge(int $userId, string $badgeKey): void
{
    db()->prepare('INSERT IGNORE INTO user_badges (user_id, badge_key) VALUES (?, ?)')->execute([$userId, $badgeKey]);
}

function get_user_badges(int $userId): array
{
    $stmt = db()->prepare('SELECT badge_key, earned_at FROM user_badges WHERE user_id = ? ORDER BY earned_at DESC');
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function loyalty_level_info(int $points): array
{
    if ($points >= 500) {
        return ['level' => 'gold', 'next' => null, 'progress' => 100];
    }
    if ($points >= 200) {
        return ['level' => 'silver', 'next' => 500, 'progress' => (int) (($points - 200) / 300 * 100)];
    }

    return ['level' => 'bronze', 'next' => 200, 'progress' => (int) ($points / 200 * 100)];
}

function record_referral_use(string $code, int $newUserId): void
{
    $stmt = db()->prepare('SELECT * FROM referral_codes WHERE code = ? LIMIT 1');
    $stmt->execute([strtoupper(trim($code))]);
    $ref = $stmt->fetch();
    if (!$ref) {
        return;
    }

    db()->prepare('INSERT INTO referral_uses (referral_code_id, referred_user_id, credits_awarded) VALUES (?, ?, ?)')
        ->execute([(int) $ref['id'], $newUserId, 10]);

    db()->prepare('UPDATE referral_codes SET uses_count = uses_count + 1 WHERE id = ?')->execute([(int) $ref['id']]);
    db()->prepare('UPDATE users SET referral_credits = referral_credits + 10 WHERE id = ?')->execute([(int) $ref['user_id']]);
    award_loyalty_points((int) $ref['user_id'], 25, 'referral');
    award_loyalty_points($newUserId, 15, 'referred');
}

function referral_leaderboard(int $orgId, int $limit = 10): array
{
    $stmt = db()->prepare(
        'SELECT u.full_name, u.referral_credits, r.code, r.uses_count
         FROM referral_codes r
         JOIN users u ON u.id = r.user_id
         WHERE r.organization_id = ?
         ORDER BY r.uses_count DESC, u.referral_credits DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $orgId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function organizer_analytics_detailed(int $orgId): array
{
    $base = organizer_analytics($orgId);

    $registered = db()->prepare(
        'SELECT COUNT(*) FROM event_participants ep JOIN events e ON e.id = ep.event_id
         WHERE e.organization_id = ?'
    );
    $registered->execute([$orgId]);
    $totalRegistered = (int) $registered->fetchColumn();

    $checkedIn = db()->prepare(
        'SELECT COUNT(*) FROM event_participants ep JOIN events e ON e.id = ep.event_id
         WHERE e.organization_id = ? AND ep.checked_in = 1'
    );
    $checkedIn->execute([$orgId]);

    $gender = db()->prepare(
        "SELECT u.gender, COUNT(*) AS cnt FROM event_participants ep
         JOIN events e ON e.id = ep.event_id JOIN users u ON u.id = ep.user_id
         WHERE e.organization_id = ? AND u.gender IS NOT NULL GROUP BY u.gender"
    );
    $gender->execute([$orgId]);

    $popular = db()->prepare(
        'SELECT e.id, e.title, COUNT(ep.id) AS participants
         FROM events e LEFT JOIN event_participants ep ON ep.event_id = e.id AND ep.registration_status = ?
         WHERE e.organization_id = ? GROUP BY e.id ORDER BY participants DESC LIMIT 5'
    );
    $popular->execute(['registered', $orgId]);

    $referrals = db()->prepare(
        'SELECT COALESCE(SUM(r.uses_count), 0) FROM referral_codes r WHERE r.organization_id = ?'
    );
    $referrals->execute([$orgId]);

    $votes = db()->prepare(
        'SELECT COUNT(*) FROM match_votes mv JOIN events e ON e.id = mv.event_id WHERE e.organization_id = ?'
    );
    $votes->execute([$orgId]);

    $attendance = $totalRegistered > 0 ? round(((int) $checkedIn->fetchColumn() / $totalRegistered) * 100, 1) : 0;
    $matchRate = $totalRegistered > 0 ? round(($base['matches'] / max(1, $totalRegistered)) * 100, 1) : 0;
    $conversion = $base['events'] > 0 ? round($totalRegistered / $base['events'], 1) : 0;

    return array_merge($base, [
        'attendance_rate' => $attendance,
        'match_rate' => $matchRate,
        'conversion' => $conversion,
        'gender_breakdown' => $gender->fetchAll(),
        'popular_events' => $popular->fetchAll(),
        'referral_uses' => (int) $referrals->fetchColumn(),
        'total_votes' => (int) $votes->fetchColumn(),
        'retention' => min(100, (int) ($attendance * 0.8 + $matchRate * 0.2)),
    ]);
}

function ai_event_suggestions(int $orgId): array
{
    $stmt = db()->prepare(
        "SELECT e.*, COUNT(ep.id) AS participant_count,
                COALESCE(SUM(CASE WHEN p.payment_status = 'approved' THEN p.amount ELSE 0 END), 0) AS revenue
         FROM events e
         LEFT JOIN event_participants ep ON ep.event_id = e.id
         LEFT JOIN payments p ON p.event_id = e.id AND p.user_id = ep.user_id
         WHERE e.organization_id = ? AND e.status IN ('completed','published','live')
         GROUP BY e.id ORDER BY participant_count DESC LIMIT 20"
    );
    $stmt->execute([$orgId]);
    $events = $stmt->fetchAll();

    if ($events === []) {
        return [
            'ticket_price' => 990,
            'best_time' => '18:00',
            'best_day' => 'Friday',
            'capacity' => 40,
            'venue_type' => 'Rooftop / lounge',
            'audience' => 'Young professionals',
            'marketing' => 'Instagram + LINE groups 2 weeks before event',
        ];
    }

    $prices = array_column($events, 'ticket_price');
    $avgPrice = array_sum(array_map('floatval', $prices)) / count($prices);
    $best = $events[0];
    $capacities = array_map(fn ($e) => (int) $e['max_participants'], $events);
    $avgCap = (int) round(array_sum($capacities) / count($capacities));

    $hourCounts = [];
    foreach ($events as $e) {
        $h = substr($e['start_time'], 0, 2);
        $hourCounts[$h] = ($hourCounts[$h] ?? 0) + (int) $e['participant_count'];
    }
    arsort($hourCounts);
    $bestHour = array_key_first($hourCounts) . ':00';

    $dayCounts = [];
    foreach ($events as $e) {
        $d = date('l', strtotime($e['event_date']));
        $dayCounts[$d] = ($dayCounts[$d] ?? 0) + (int) $e['participant_count'];
    }
    arsort($dayCounts);

    $types = array_count_values(array_column($events, 'event_type'));
    arsort($types);
    $topType = array_key_first($types) ?: 'social';

    return [
        'ticket_price' => (int) round($avgPrice * 0.95 / 10) * 10,
        'best_time' => $bestHour,
        'best_day' => array_key_first($dayCounts) ?: 'Friday',
        'capacity' => max(20, min($avgCap, (int) round($avgCap * 1.1))),
        'venue_type' => $best['city'] ? ($best['city'] . ' — ' . ($best['location'] ?? 'central venue')) : 'Central Bangkok venue',
        'audience' => event_type_label($topType) . ' seekers',
        'marketing' => 'Promote on Instagram Reels + LINE 10–14 days out; early-bird at ' . format_currency((float) round($avgPrice * 0.85)),
        'confidence' => min(95, 60 + count($events) * 3),
    ];
}

function notify_payment_approved(int $userId, int $eventId): void
{
    $event = get_event_by_id($eventId);
    notify_user($userId, 'payment_approved', __('notify.payment_title'), __('notify.payment_body', [
        'event' => $event['title'] ?? '',
    ]), ['event_id' => $eventId], ['in_app', 'email']);
    award_loyalty_points($userId, 20, 'payment');
}

function notify_event_reminder(int $userId, int $eventId): void
{
    $event = get_event_by_id($eventId);
    notify_user($userId, 'event_reminder', __('notify.reminder_title'), __('notify.reminder_body', [
        'event' => $event['title'] ?? '',
        'date' => format_date($event['event_date'] ?? ''),
    ]), ['event_id' => $eventId], ['in_app', 'email', 'line']);
}

function event_reminder_already_sent(int $eventId, int $userId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM event_reminder_logs WHERE event_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$eventId, $userId]);

    return (bool) $stmt->fetchColumn();
}

function log_event_reminder_sent(int $eventId, int $userId): void
{
    db()->prepare('INSERT IGNORE INTO event_reminder_logs (event_id, user_id) VALUES (?, ?)')
        ->execute([$eventId, $userId]);
}

function send_due_event_reminders(int $hoursAhead = 24): array
{
    $hoursAhead = max(1, min(168, $hoursAhead));
    $sent = 0;
    $eventsProcessed = 0;
    $errors = [];

    $stmt = db()->prepare(
        "SELECT id, title, event_date, start_time
         FROM events
         WHERE status IN ('published', 'live')
           AND TIMESTAMP(event_date, start_time) > NOW()
           AND TIMESTAMP(event_date, start_time) <= DATE_ADD(NOW(), INTERVAL ? HOUR)
         ORDER BY event_date ASC, start_time ASC"
    );
    $stmt->execute([$hoursAhead]);
    $events = $stmt->fetchAll();

    foreach ($events as $event) {
        ++$eventsProcessed;
        $eventId = (int) $event['id'];

        $participants = db()->prepare(
            "SELECT user_id FROM event_participants
             WHERE event_id = ? AND registration_status = 'registered' AND payment_status = 'approved'"
        );
        $participants->execute([$eventId]);

        foreach ($participants->fetchAll() as $row) {
            $userId = (int) $row['user_id'];
            if (event_reminder_already_sent($eventId, $userId)) {
                continue;
            }

            try {
                notify_event_reminder($userId, $eventId);
                log_event_reminder_sent($eventId, $userId);
                ++$sent;
            } catch (Throwable $e) {
                $errors[] = "Event {$eventId}, user {$userId}: " . $e->getMessage();
            }
        }
    }

    return [
        'sent' => $sent,
        'events' => $eventsProcessed,
        'hours_ahead' => $hoursAhead,
        'errors' => $errors,
    ];
}
