<?php

declare(strict_types=1);

function ensure_platform_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $userColumns = [
        'cover_image' => 'VARCHAR(255) NULL AFTER avatar',
        'bio' => 'TEXT NULL AFTER cover_image',
        'gender' => "ENUM('male','female','non_binary','prefer_not_to_say','other') NULL AFTER bio",
        'date_of_birth' => 'DATE NULL AFTER gender',
        'interests' => 'JSON NULL AFTER date_of_birth',
        'personality' => 'VARCHAR(80) NULL AFTER interests',
        'languages' => 'JSON NULL AFTER personality',
        'city' => 'VARCHAR(100) NULL AFTER languages',
        'occupation' => 'VARCHAR(120) NULL AFTER city',
        'instagram' => 'VARCHAR(120) NULL AFTER occupation',
        'facebook' => 'VARCHAR(120) NULL AFTER instagram',
        'privacy_settings' => 'JSON NULL AFTER facebook',
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    ];

    foreach ($userColumns as $column => $definition) {
        if (!table_has_column('users', $column)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
        }
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_compatibility_profiles (
            user_id INT UNSIGNED PRIMARY KEY,
            interests JSON NULL,
            personality_type VARCHAR(80) NULL,
            communication_style VARCHAR(80) NULL,
            relationship_goal VARCHAR(80) NULL,
            networking_goal VARCHAR(80) NULL,
            icebreaker_preferences JSON NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $eventColumns = [
        'banner_image' => 'VARCHAR(255) NULL AFTER cover_image',
        'dress_code' => 'VARCHAR(200) NULL AFTER map_url',
        'rules' => 'TEXT NULL AFTER dress_code',
        'waitlist_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER rules',
        'invite_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER waitlist_enabled',
        'round_count' => 'INT UNSIGNED NOT NULL DEFAULT 5 AFTER round_duration',
    ];

    foreach ($eventColumns as $column => $definition) {
        if (!table_has_column('events', $column)) {
            $pdo->exec("ALTER TABLE events ADD COLUMN {$column} {$definition}");
        }
    }

    if (table_has_column('events', 'event_type')) {
        $pdo->exec(
            "ALTER TABLE events MODIFY event_type ENUM(
                'dating','networking','friendship','startup','business','recruitment',
                'university','corporate','lgbtq','speed_networking','professional_mixer',
                'private_event','mixer','speed_dating','workshop','social','other'
            ) NOT NULL DEFAULT 'social'"
        );
    }

    $participantColumns = [
        'registration_status' => "ENUM('registered','waitlist','cancelled') NOT NULL DEFAULT 'registered' AFTER user_id",
        'coupon_id' => 'INT UNSIGNED NULL AFTER registration_status',
        'invited_by' => 'INT UNSIGNED NULL AFTER coupon_id',
        'invite_token' => 'VARCHAR(64) NULL AFTER invited_by',
    ];

    foreach ($participantColumns as $column => $definition) {
        if (!table_has_column('event_participants', $column)) {
            $pdo->exec("ALTER TABLE event_participants ADD COLUMN {$column} {$definition}");
        }
    }

    if (!table_has_column('payments', 'coupon_id')) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN coupon_id INT UNSIGNED NULL AFTER amount');
        $pdo->exec('ALTER TABLE payments ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_id');
        $pdo->exec('ALTER TABLE payments ADD COLUMN original_amount DECIMAL(10,2) NULL AFTER discount_amount');
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS coupons (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            event_id INT UNSIGNED NULL,
            code VARCHAR(40) NOT NULL,
            discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL,
            max_uses INT UNSIGNED NULL,
            used_count INT UNSIGNED NOT NULL DEFAULT 0,
            expires_at TIMESTAMP NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            UNIQUE KEY unique_coupon_code (organization_id, code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_invitations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            inviter_id INT UNSIGNED NOT NULL,
            invitee_email VARCHAR(180) NOT NULL,
            invite_token VARCHAR(64) NOT NULL UNIQUE,
            status ENUM('pending','accepted','expired') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS referral_codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            code VARCHAR(40) NOT NULL UNIQUE,
            reward_description VARCHAR(255) NULL,
            uses_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            subject VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    seed_platform_categories();
    $ready = true;
}

function seed_platform_categories(): void
{
    $categories = [
        ['Dating', 'dating', 'event'],
        ['Networking', 'networking-cat', 'event'],
        ['Friendship', 'friendship', 'event'],
        ['Startup', 'startup', 'event'],
        ['Business', 'business', 'event'],
        ['Recruitment', 'recruitment', 'event'],
        ['University', 'university', 'event'],
        ['Corporate', 'corporate', 'event'],
        ['LGBTQ+', 'lgbtq', 'event'],
        ['Speed Networking', 'speed-networking', 'event'],
        ['Professional Mixer', 'professional-mixer', 'event'],
        ['Private Event', 'private-event', 'event'],
    ];

    $stmt = db()->prepare('INSERT IGNORE INTO categories (name, slug, type) VALUES (?, ?, ?)');
    foreach ($categories as [$name, $slug, $type]) {
        $stmt->execute([$name, $slug, $type]);
    }
}

function platform_event_types(): array
{
    return [
        'dating', 'networking', 'friendship', 'startup', 'business', 'recruitment',
        'university', 'corporate', 'lgbtq', 'speed_networking', 'professional_mixer',
        'private_event', 'mixer', 'speed_dating', 'workshop', 'social', 'other',
    ];
}

function gender_options(): array
{
    return ['male', 'female', 'non_binary', 'prefer_not_to_say', 'other'];
}

function personality_types(): array
{
    return ['introvert', 'extrovert', 'ambivert', 'analytical', 'creative', 'empathetic', 'direct'];
}

function communication_styles(): array
{
    return ['listener', 'storyteller', 'question_asker', 'humorous', 'deep_talker', 'light_chat'];
}

function relationship_goals(): array
{
    return ['dating', 'friendship', 'networking', 'mentorship', 'collaboration', 'open'];
}

function networking_goals(): array
{
    return ['founders', 'investors', 'creatives', 'professionals', 'community', 'general'];
}

function get_user_profile(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }

    $user['interests'] = json_decode($user['interests'] ?? '[]', true) ?: [];
    $user['languages'] = json_decode($user['languages'] ?? '[]', true) ?: [];
    $user['privacy_settings'] = json_decode($user['privacy_settings'] ?? '{}', true) ?: default_privacy_settings();

    $comp = db()->prepare('SELECT * FROM user_compatibility_profiles WHERE user_id = ?');
    $comp->execute([$userId]);
    $user['compatibility'] = $comp->fetch() ?: [];
    if ($user['compatibility']) {
        $user['compatibility']['interests'] = json_decode($user['compatibility']['interests'] ?? '[]', true) ?: [];
        $user['compatibility']['icebreaker_preferences'] = json_decode($user['compatibility']['icebreaker_preferences'] ?? '[]', true) ?: [];
    }

    return $user;
}

function default_privacy_settings(): array
{
    return [
        'show_email' => false,
        'show_phone' => false,
        'show_social' => true,
        'show_bio' => true,
        'show_compatibility' => true,
    ];
}

function save_user_profile(int $userId, array $data, ?string $avatarPath = null, ?string $coverPath = null): void
{
    $interests = array_values(array_filter(array_map('trim', $data['interests'] ?? [])));
    $languages = array_values(array_filter(array_map('trim', $data['languages'] ?? [])));
    $privacy = array_merge(default_privacy_settings(), $data['privacy_settings'] ?? []);

    $stmt = db()->prepare(
        'UPDATE users SET full_name=?, phone=?, line_id=?, bio=?, gender=?, date_of_birth=?,
            interests=?, personality=?, languages=?, city=?, occupation=?, instagram=?, facebook=?,
            privacy_settings=?, avatar=COALESCE(?, avatar), cover_image=COALESCE(?, cover_image)
         WHERE id=?'
    );
    $stmt->execute([
        $data['full_name'],
        $data['phone'] ?: null,
        $data['line_id'] ?: null,
        $data['bio'] ?: null,
        in_array($data['gender'] ?? '', gender_options(), true) ? $data['gender'] : null,
        $data['date_of_birth'] ?: null,
        json_encode($interests),
        $data['personality'] ?: null,
        json_encode($languages),
        $data['city'] ?: null,
        $data['occupation'] ?: null,
        $data['instagram'] ?: null,
        $data['facebook'] ?: null,
        json_encode($privacy),
        $avatarPath,
        $coverPath,
        $userId,
    ]);
}

function save_compatibility_profile(int $userId, array $data): void
{
    $interests = array_values(array_filter(array_map('trim', $data['interests'] ?? [])));
    $icebreakers = array_values(array_filter(array_map('trim', $data['icebreaker_preferences'] ?? [])));

    $exists = db()->prepare('SELECT user_id FROM user_compatibility_profiles WHERE user_id = ?');
    $exists->execute([$userId]);

    if ($exists->fetch()) {
        $stmt = db()->prepare(
            'UPDATE user_compatibility_profiles SET interests=?, personality_type=?, communication_style=?,
                relationship_goal=?, networking_goal=?, icebreaker_preferences=? WHERE user_id=?'
        );
    } else {
        $stmt = db()->prepare(
            'INSERT INTO user_compatibility_profiles (interests, personality_type, communication_style,
                relationship_goal, networking_goal, icebreaker_preferences, user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
    }

    $stmt->execute([
        json_encode($interests),
        $data['personality_type'] ?: null,
        $data['communication_style'] ?: null,
        $data['relationship_goal'] ?: null,
        $data['networking_goal'] ?: null,
        json_encode($icebreakers),
        $userId,
    ]);
}

function register_account(string $fullName, string $email, string $password, string $role, ?string $orgName = null): array
{
    $email = strtolower(trim($email));
    $check = db()->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => __('validation.email_taken')];
    }

    if (!in_array($role, ['participant', 'organizer'], true)) {
        return ['ok' => false, 'error' => __('validation.invalid_role')];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $insert = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $insert->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        $userId = (int) $pdo->lastInsertId();

        if ($role === 'organizer') {
            $name = $orgName ?: ($fullName . "'s Organization");
            $slug = unique_org_slug($name);
            $org = $pdo->prepare(
                'INSERT INTO organizations (name, slug, owner_id) VALUES (?, ?, ?)'
            );
            $org->execute([$name, $slug, $userId]);
            $orgId = (int) $pdo->lastInsertId();
            add_org_member($orgId, $userId, 'owner');
            ensure_org_subscription($orgId);
        }

        $pdo->commit();

        return ['ok' => true, 'user_id' => $userId];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Registration failed: ' . $e->getMessage());

        return ['ok' => false, 'error' => __('validation.registration_failed')];
    }
}

function unique_org_slug(string $name): string
{
    $base = slugify($name) ?: 'org';
    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM organizations WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function event_spots_available(int $eventId): int
{
    $event = get_event_by_id($eventId);
    if (!$event) {
        return 0;
    }
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND registration_status = 'registered'"
    );
    $stmt->execute([$eventId]);

    return max(0, (int) $event['max_participants'] - (int) $stmt->fetchColumn());
}

function get_user_event_registration(int $eventId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM event_participants WHERE event_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$eventId, $userId]);

    return $stmt->fetch() ?: null;
}

function join_event(int $eventId, int $userId, float $amount, ?int $couponId = null, ?int $invitedBy = null): array
{
    $existing = get_user_event_registration($eventId, $userId);
    if ($existing && $existing['registration_status'] !== 'cancelled') {
        return ['ok' => false, 'error' => __('validation.already_registered')];
    }

    $event = get_event_by_id($eventId);
    if (!$event) {
        return ['ok' => false, 'error' => __('flash.event_not_found')];
    }

    $spots = event_spots_available($eventId);
    $waitlist = $spots <= 0 && !empty($event['waitlist_enabled']);

    if ($spots <= 0 && !$waitlist) {
        return ['ok' => false, 'error' => __('validation.sold_out')];
    }

    $finalAmount = $amount;
    $discount = 0.0;
    if ($couponId) {
        $coupon = get_coupon_by_id($couponId);
        if ($coupon && coupon_valid($coupon, $eventId)) {
            [$finalAmount, $discount] = apply_coupon_discount($amount, $coupon);
        }
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($existing) {
            $upd = $pdo->prepare(
                "UPDATE event_participants SET registration_status=?, payment_status=?, ticket_status='none',
                    coupon_id=?, invited_by=?, checked_in=0 WHERE id=?"
            );
            $upd->execute([
                $waitlist ? 'waitlist' : 'registered',
                $waitlist ? 'none' : 'pending',
                $couponId,
                $invitedBy,
                $existing['id'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO event_participants (event_id, user_id, registration_status, payment_status, ticket_status, coupon_id, invited_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $eventId,
                $userId,
                $waitlist ? 'waitlist' : 'registered',
                $waitlist ? 'none' : 'pending',
                'none',
                $couponId,
                $invitedBy,
            ]);
        }

        if (!$waitlist) {
            $payment = $pdo->prepare(
                'INSERT INTO payments (event_id, user_id, amount, coupon_id, discount_amount, original_amount, payment_method, payment_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $payment->execute([
                $eventId, $userId, $finalAmount, $couponId, $discount, $amount,
                'promptpay', $finalAmount <= 0 ? 'approved' : 'pending',
            ]);

            if ($finalAmount <= 0) {
                $pdo->prepare('UPDATE event_participants SET payment_status = ? WHERE event_id = ? AND user_id = ?')
                    ->execute(['approved', $eventId, $userId]);
                generate_ticket($eventId, $userId);
            }

            if ($couponId) {
                $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?')->execute([$couponId]);
            }
        }

        $pdo->commit();

        return [
            'ok' => true,
            'waitlist' => $waitlist,
            'free' => $finalAmount <= 0,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Join event failed: ' . $e->getMessage());

        return ['ok' => false, 'error' => __('validation.registration_failed')];
    }
}

function cancel_event_registration(int $eventId, int $userId): bool
{
    $reg = get_user_event_registration($eventId, $userId);
    if (!$reg || $reg['registration_status'] === 'cancelled') {
        return false;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $pdo->prepare(
            "UPDATE event_participants SET registration_status='cancelled', payment_status='none', ticket_status='none' WHERE event_id=? AND user_id=?"
        )->execute([$eventId, $userId]);

        $pdo->prepare('DELETE FROM tickets WHERE event_id=? AND user_id=?')->execute([$eventId, $userId]);
        $pdo->prepare(
            "UPDATE payments SET payment_status='rejected' WHERE event_id=? AND user_id=? AND payment_status='pending'"
        )->execute([$eventId, $userId]);

        $pdo->commit();
        promote_waitlist($eventId);

        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();

        return false;
    }
}

function promote_waitlist(int $eventId): void
{
    if (event_spots_available($eventId) <= 0) {
        return;
    }

    $stmt = db()->prepare(
        "SELECT * FROM event_participants WHERE event_id = ? AND registration_status = 'waitlist' ORDER BY created_at ASC LIMIT 1"
    );
    $stmt->execute([$eventId]);
    $next = $stmt->fetch();
    if (!$next) {
        return;
    }

    $event = get_event_by_id($eventId);
    if (!$event) {
        return;
    }

    db()->prepare(
        "UPDATE event_participants SET registration_status='registered', payment_status='pending' WHERE id=?"
    )->execute([(int) $next['id']]);

    db()->prepare(
        'INSERT INTO payments (event_id, user_id, amount, payment_method, payment_status, original_amount)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $eventId,
        (int) $next['user_id'],
        (float) $event['ticket_price'],
        'promptpay',
        'pending',
        (float) $event['ticket_price'],
    ]);
}

function get_coupon_by_code(string $code, int $orgId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM coupons WHERE organization_id = ? AND UPPER(code) = UPPER(?) AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([$orgId, trim($code)]);

    return $stmt->fetch() ?: null;
}

function get_coupon_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM coupons WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function coupon_valid(array $coupon, int $eventId): bool
{
    if (!$coupon['is_active']) {
        return false;
    }
    if ($coupon['event_id'] && (int) $coupon['event_id'] !== $eventId) {
        return false;
    }
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        return false;
    }
    if ($coupon['max_uses'] && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
        return false;
    }

    return true;
}

function apply_coupon_discount(float $amount, array $coupon): array
{
    if ($coupon['discount_type'] === 'fixed') {
        $discount = min($amount, (float) $coupon['discount_value']);
    } else {
        $discount = round($amount * ((float) $coupon['discount_value'] / 100), 2);
    }

    return [max(0, $amount - $discount), $discount];
}

function create_event_invitation(int $eventId, int $inviterId, string $email): ?string
{
    $token = bin2hex(random_bytes(16));
    $stmt = db()->prepare(
        'INSERT INTO event_invitations (event_id, inviter_id, invitee_email, invite_token) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$eventId, $inviterId, strtolower(trim($email)), $token]);

    return base_url('register.php?event_id=' . $eventId . '&invite=' . $token);
}

function get_invitation_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT * FROM event_invitations WHERE invite_token = ? LIMIT 1');
    $stmt->execute([$token]);

    return $stmt->fetch() ?: null;
}

function get_user_registrations(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT ep.*, e.title, e.slug, e.event_date, e.start_time, e.location, e.city, e.ticket_price, e.status AS event_status
         FROM event_participants ep
         JOIN events e ON e.id = ep.event_id
         WHERE ep.user_id = ? AND ep.registration_status != 'cancelled'
         ORDER BY e.event_date ASC"
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function calculate_compatibility(int $userAId, int $userBId): int
{
    return ai_ethical_compatibility_score($userAId, $userBId);
}

function get_compatibility_data(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT u.city, u.interests AS user_interests, c.*
         FROM users u
         LEFT JOIN user_compatibility_profiles c ON c.user_id = u.id
         WHERE u.id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $profileInterests = json_decode($row['interests'] ?? '[]', true) ?: [];
    $userInterests = json_decode($row['user_interests'] ?? '[]', true) ?: [];
    $row['interests'] = array_values(array_unique(array_merge($profileInterests, $userInterests)));

    return $row;
}

function conversation_suggestions(int $userAId, int $userBId): array
{
    $a = get_compatibility_data($userAId);
    $b = get_compatibility_data($userBId);
    $suggestions = [];

    $shared = array_intersect(
        array_map('strtolower', $a['interests'] ?? []),
        array_map('strtolower', $b['interests'] ?? [])
    );

    foreach (array_slice($shared, 0, 2) as $interest) {
        $suggestions[] = __('matching.ask_interest', ['interest' => ucfirst($interest)]);
    }

    if (!empty($a['networking_goal'])) {
        $suggestions[] = __('matching.ask_networking', ['goal' => __($a['networking_goal'] ?? 'networking.general')]);
    }

    $icebreakers = json_decode($b['icebreaker_preferences'] ?? '[]', true) ?: [];
    foreach (array_slice($icebreakers, 0, 2) as $topic) {
        $suggestions[] = __('matching.icebreaker_topic', ['topic' => $topic]);
    }

    if ($suggestions === []) {
        $suggestions = [
            __('matching.default_1'),
            __('matching.default_2'),
            __('matching.default_3'),
        ];
    }

    return array_slice($suggestions, 0, 4);
}

function best_matches_for_user(int $userId, int $eventId, int $limit = 5): array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.full_name, u.avatar, u.city
         FROM event_participants ep
         JOIN users u ON u.id = ep.user_id
         WHERE ep.event_id = ? AND ep.user_id != ? AND ep.registration_status = 'registered'
           AND ep.payment_status = 'approved'"
    );
    $stmt->execute([$eventId, $userId]);
    $others = $stmt->fetchAll();

    $scored = [];
    foreach ($others as $other) {
        $score = calculate_compatibility($userId, (int) $other['id']);
        $scored[] = array_merge($other, [
            'compatibility' => $score,
            'starters' => conversation_suggestions($userId, (int) $other['id']),
        ]);
    }

    usort($scored, fn ($x, $y) => $y['compatibility'] <=> $x['compatibility']);

    return array_slice($scored, 0, $limit);
}

function generate_ai_round_pairings(int $eventId, int $roundNumber): array
{
    $participants = get_checked_in_participants($eventId);
    $count = count($participants);
    if ($count < 2) {
        return [];
    }

    $previous = get_previous_pairings($eventId);
    $ids = array_column($participants, 'id');
    $candidates = [];

    for ($i = 0; $i < $count; $i++) {
        for ($j = $i + 1; $j < $count; $j++) {
            $a = (int) $ids[$i];
            $b = (int) $ids[$j];
            $key = min($a, $b) . '-' . max($a, $b);
            if (isset($previous[$key])) {
                continue;
            }
            if (users_are_blocked($a, $b)) {
                continue;
            }
            $score = calculate_compatibility($a, $b);
            if ($score <= 0) {
                continue;
            }
            $candidates[] = [
                'a' => $a,
                'b' => $b,
                'score' => $score,
            ];
        }
    }

    usort($candidates, fn ($x, $y) => $y['score'] <=> $x['score']);

    $pairs = [];
    $used = [];
    $tableNumber = 1;

    foreach ($candidates as $c) {
        if (isset($used[$c['a']]) || isset($used[$c['b']])) {
            continue;
        }
        $pairs[] = [
            'round_number' => $roundNumber,
            'table_number' => $tableNumber++,
            'participant_a' => $c['a'],
            'participant_b' => $c['b'],
            'compatibility_score' => $c['score'],
        ];
        $used[$c['a']] = true;
        $used[$c['b']] = true;
    }

    if (count($pairs) < (int) floor($count / 2)) {
        return generate_round_pairings($eventId, $roundNumber);
    }

    $event = get_event_by_id($eventId);
    log_ai_usage('round_pairings', $event ? (int) $event['organization_id'] : null, null, $eventId, [
        'round' => $roundNumber,
        'pairs' => count($pairs),
    ]);

    return $pairs;
}

function organizer_analytics(int $orgId): array
{
    $events = db()->prepare('SELECT COUNT(*) FROM events WHERE organization_id = ?');
    $events->execute([$orgId]);

    $participants = db()->prepare(
        'SELECT COUNT(*) FROM event_participants ep JOIN events e ON e.id = ep.event_id
         WHERE e.organization_id = ? AND ep.registration_status = ?'
    );
    $participants->execute([$orgId, 'registered']);

    $waitlist = db()->prepare(
        'SELECT COUNT(*) FROM event_participants ep JOIN events e ON e.id = ep.event_id
         WHERE e.organization_id = ? AND ep.registration_status = ?'
    );
    $waitlist->execute([$orgId, 'waitlist']);

    $revenue = db()->prepare(
        'SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN events e ON e.id = p.event_id
         WHERE e.organization_id = ? AND p.payment_status = ?'
    );
    $revenue->execute([$orgId, 'approved']);

    $matches = db()->prepare(
        'SELECT COUNT(*) FROM matches m JOIN events e ON e.id = m.event_id WHERE e.organization_id = ?'
    );
    $matches->execute([$orgId]);

    return [
        'events' => (int) $events->fetchColumn(),
        'participants' => (int) $participants->fetchColumn(),
        'waitlist' => (int) $waitlist->fetchColumn(),
        'revenue' => (float) $revenue->fetchColumn(),
        'matches' => (int) $matches->fetchColumn(),
    ];
}

function get_event_waitlist(int $eventId): array
{
    $stmt = db()->prepare(
        "SELECT ep.*, u.full_name, u.email, u.phone
         FROM event_participants ep
         JOIN users u ON u.id = ep.user_id
         WHERE ep.event_id = ? AND ep.registration_status = 'waitlist'
         ORDER BY ep.created_at ASC"
    );
    $stmt->execute([$eventId]);

    return $stmt->fetchAll();
}

function get_org_coupons(int $orgId): array
{
    $stmt = db()->prepare(
        'SELECT c.*, e.title AS event_title FROM coupons c
         LEFT JOIN events e ON e.id = c.event_id
         WHERE c.organization_id = ? ORDER BY c.created_at DESC'
    );
    $stmt->execute([$orgId]);

    return $stmt->fetchAll();
}

function save_coupon(int $orgId, array $data): bool
{
    $stmt = db()->prepare(
        'INSERT INTO coupons (organization_id, event_id, code, discount_type, discount_value, max_uses, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    try {
        $stmt->execute([
            $orgId,
            !empty($data['event_id']) ? (int) $data['event_id'] : null,
            strtoupper(trim($data['code'])),
            in_array($data['discount_type'], ['percent', 'fixed'], true) ? $data['discount_type'] : 'percent',
            (float) $data['discount_value'],
            !empty($data['max_uses']) ? (int) $data['max_uses'] : null,
            !empty($data['expires_at']) ? $data['expires_at'] : null,
        ]);

        return true;
    } catch (PDOException) {
        return false;
    }
}

function get_or_create_referral_code(int $orgId, int $userId): string
{
    $stmt = db()->prepare('SELECT code FROM referral_codes WHERE organization_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$orgId, $userId]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (string) $existing;
    }

    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    db()->prepare(
        'INSERT INTO referral_codes (organization_id, user_id, code, reward_description) VALUES (?, ?, ?, ?)'
    )->execute([$orgId, $userId, $code, '10% off next event']);

    return $code;
}

function get_event_matches(int $eventId): array
{
    $stmt = db()->prepare(
        'SELECT m.*, ua.full_name AS user_a_name, ub.full_name AS user_b_name
         FROM matches m
         JOIN users ua ON ua.id = m.user_a
         JOIN users ub ON ub.id = m.user_b
         WHERE m.event_id = ?
         ORDER BY m.created_at DESC'
    );
    $stmt->execute([$eventId]);

    return $stmt->fetchAll();
}

function parse_event_builder_data(array $post): array
{
    $base = parse_event_form_data($post);

    return array_merge($base, [
        'dress_code' => trim($post['dress_code'] ?? ''),
        'rules' => trim($post['rules'] ?? ''),
        'waitlist_enabled' => isset($post['waitlist_enabled']) ? 1 : 0,
        'invite_enabled' => isset($post['invite_enabled']) ? 1 : 0,
        'round_count' => max(1, (int) ($post['round_count'] ?? 5)),
    ]);
}

function update_event_builder_fields(int $eventId, array $data): void
{
    db()->prepare(
        'UPDATE events SET dress_code=?, rules=?, waitlist_enabled=?, invite_enabled=?, round_count=? WHERE id=?'
    )->execute([
        $data['dress_code'] ?: null,
        $data['rules'] ?: null,
        $data['waitlist_enabled'] ?? 1,
        $data['invite_enabled'] ?? 1,
        $data['round_count'] ?? 5,
        $eventId,
    ]);
}

function create_event_record_extended(int $orgId, int $userId, array $data, ?string $coverPath, ?string $ogImagePath, ?string $bannerPath): int
{
    $eventId = create_event_record($orgId, $userId, $data, $coverPath, $ogImagePath);
    if ($bannerPath) {
        db()->prepare('UPDATE events SET banner_image = ? WHERE id = ?')->execute([$bannerPath, $eventId]);
    }
    update_event_builder_fields($eventId, $data);

    return $eventId;
}

function update_event_record_extended(int $eventId, array $data, ?string $coverPath, ?string $ogImagePath, ?string $bannerPath): void
{
    update_event_record($eventId, $data, $coverPath, $ogImagePath);
    if ($bannerPath) {
        db()->prepare('UPDATE events SET banner_image = ? WHERE id = ?')->execute([$bannerPath, $eventId]);
    }
    update_event_builder_fields($eventId, $data);
}

function reject_ticket_checkin(int $ticketId, int $organizerId): bool
{
    $stmt = db()->prepare('SELECT * FROM tickets WHERE id = ? LIMIT 1');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        return false;
    }

    db()->prepare('UPDATE tickets SET checked_in = 0, checked_in_at = NULL WHERE id = ?')->execute([$ticketId]);
    db()->prepare('UPDATE event_participants SET checked_in = 0 WHERE event_id = ? AND user_id = ?')
        ->execute([(int) $ticket['event_id'], (int) $ticket['user_id']]);

    log_admin_action($organizerId, 'ticket_rejected', 'Ticket #' . $ticketId);

    return true;
}

function manual_checkin_participant(int $eventId, int $userId): bool
{
    $token = generate_ticket($eventId, $userId);
    if (!$token) {
        return false;
    }

    $result = checkin_ticket($token);

    return $result['success'] ?? false;
}

function participant_session_user_id(): int
{
    if (is_logged_in() && current_user_role() === 'participant') {
        return (int) current_user()['id'];
    }

    return (int) ($_SESSION['participant_user_id'] ?? 0);
}

function set_participant_session(int $userId, int $eventId): void
{
    $_SESSION['participant_user_id'] = $userId;
    $_SESSION['participant_event_id'] = $eventId;
}
