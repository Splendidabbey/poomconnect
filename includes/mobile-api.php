<?php

declare(strict_types=1);

function ensure_mobile_api_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec(
        "CREATE TABLE IF NOT EXISTS mobile_api_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            device_name VARCHAR(120) NULL,
            expires_at TIMESTAMP NULL,
            last_used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_mobile_token_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ready = true;
}

function mobile_json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, X-Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function mobile_handle_options(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        mobile_json_response(['success' => true]);
    }
}

function mobile_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function mobile_create_token(int $userId, ?string $deviceName = null, int $daysValid = 30): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + ($daysValid * 86400));

    db()->prepare(
        'INSERT INTO mobile_api_tokens (user_id, token_hash, device_name, expires_at) VALUES (?, ?, ?, ?)'
    )->execute([$userId, $hash, $deviceName, $expires]);

    return $token;
}

function mobile_revoke_token(string $token): void
{
    $hash = hash('sha256', $token);
    db()->prepare('DELETE FROM mobile_api_tokens WHERE token_hash = ?')->execute([$hash]);
}

function mobile_revoke_user_tokens(int $userId): void
{
    db()->prepare('DELETE FROM mobile_api_tokens WHERE user_id = ?')->execute([$userId]);
}

function mobile_bearer_token(): ?string
{
    // Prefer Authorization; X-Authorization is a FastCGI/MAMP fallback when Apache strips it.
    // Treat empty strings as missing — rewrite rules can set REDIRECT_* to "".
    $candidates = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        $_SERVER['HTTP_X_AUTHORIZATION'] ?? null,
        $_SERVER['REDIRECT_HTTP_X_AUTHORIZATION'] ?? null,
    ];
    $header = '';
    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '') {
            $header = $candidate;
            break;
        }
    }

    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            $key = strtolower((string) $name);
            if ($key === 'authorization' || $key === 'x-authorization') {
                $header = (string) $value;
                break;
            }
        }
    }

    if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        return $matches[1];
    }

    // Raw token without Bearer prefix (some proxies strip the scheme)
    if ($header !== '' && !str_contains($header, ' ')) {
        return $header;
    }

    return null;
}

function mobile_authenticate_request(): ?array
{
    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $token = mobile_bearer_token();
    if (!$token) {
        return null;
    }

    $hash = hash('sha256', $token);
    $stmt = db()->prepare(
        'SELECT t.*, u.* FROM mobile_api_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? LIMIT 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
        db()->prepare('DELETE FROM mobile_api_tokens WHERE id = ?')->execute([(int) $row['id']]);

        return null;
    }

    if (function_exists('user_is_active') && !user_is_active($row)) {
        return null;
    }

    db()->prepare('UPDATE mobile_api_tokens SET last_used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);

    $_SESSION['user_id'] = (int) $row['user_id'];
    $_SESSION['user_role'] = $row['role'];
    $_SESSION['participant_user_id'] = (int) $row['user_id'];

    $cachedUser = $row;

    return $row;
}

function mobile_require_auth(array $roles = []): array
{
    $user = mobile_authenticate_request();
    if (!$user) {
        mobile_json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        mobile_json_response(['success' => false, 'message' => 'Forbidden'], 403);
    }

    return $user;
}

function mobile_user_payload(array $user): array
{
    $org = function_exists('get_organization_for_user')
        ? get_organization_for_user((int) $user['id'])
        : null;

    return [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'phone' => $user['phone'] ?? null,
        'avatar' => !empty($user['avatar']) ? upload_url($user['avatar']) : default_avatar($user['full_name'] ?? ''),
        'cover' => !empty($user['cover_image']) ? upload_url($user['cover_image']) : null,
        'city' => $user['city'] ?? null,
        'bio' => $user['bio'] ?? null,
        'verified' => !empty($user['verified_at']),
        'is_vip' => !empty($user['is_vip']),
        'is_host' => in_array($user['role'] ?? '', ['organizer', 'admin', 'super_admin'], true) || $org !== null,
        'points' => (int) ($user['loyalty_points'] ?? 0),
    ];
}

function mobile_event_payload(array $event, ?int $userId = null): array
{
    $eventId = (int) $event['id'];
    $registration = null;

    if ($userId) {
        $reg = get_user_event_registration($eventId, $userId);
        if ($reg && $reg['registration_status'] !== 'cancelled') {
            $registration = [
                'status' => $reg['registration_status'],
                'payment_status' => $reg['payment_status'],
                'ticket_status' => $reg['ticket_status'],
            ];
        }
    }

    return [
        'id' => $eventId,
        'title' => $event['title'],
        'slug' => $event['slug'] ?? null,
        'description' => $event['description'] ?? '',
        'event_date' => $event['event_date'],
        'start_time' => $event['start_time'],
        'end_time' => $event['end_time'] ?? null,
        'location' => $event['location'] ?? '',
        'city' => $event['city'] ?? '',
        'ticket_price' => (float) $event['ticket_price'],
        'currency' => current_currency(),
        'max_participants' => (int) $event['max_participants'],
        'participant_count' => (int) ($event['participant_count'] ?? 0),
        'spots_available' => event_spots_available($eventId),
        'status' => $event['status'],
        'event_type' => $event['event_type'] ?? 'social',
        'cover_image' => !empty($event['cover_image']) ? upload_url($event['cover_image']) : null,
        'banner_image' => !empty($event['banner_image']) ? upload_url($event['banner_image']) : null,
        'organization_name' => $event['organization_name'] ?? null,
        'category_name' => $event['category_name'] ?? null,
        'dress_code' => $event['dress_code'] ?? null,
        'rules' => $event['rules'] ?? null,
        'round_duration' => (int) ($event['round_duration'] ?? 300),
        'is_featured' => ($event['status'] ?? '') === 'live',
        'registration' => $registration,
    ];
}

function mobile_require_host(): array
{
    $user = mobile_require_auth();
    $org = ensure_member_organization($user);
    if (!$org && !is_admin()) {
        mobile_json_response(['success' => false, 'message' => 'Host studio is unavailable'], 403);
    }

    $fresh = current_user(true);

    return [
        'user' => is_array($fresh) ? $fresh : $user,
        'org' => $org,
    ];
}

function mobile_get_event_for_host(int $eventId): ?array
{
    $event = get_event_by_id($eventId);
    $user = current_user();
    if (!$event || !$user || !user_can_manage_event((int) $user['id'], $event)) {
        return null;
    }

    return $event;
}

function mobile_get_payment_for_host(int $paymentId): ?array
{
    $user = current_user();
    if (!$user) {
        return null;
    }

    $org = get_organization_for_user((int) $user['id']);
    $sql = 'SELECT p.*, e.organization_id FROM payments p JOIN events e ON e.id = p.event_id WHERE p.id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    if (!$payment) {
        return null;
    }

    if (!is_admin() && (!$org || (int) $payment['organization_id'] !== (int) $org['id'])) {
        return null;
    }

    return $payment;
}

function mobile_match_payload(array $row, int $userId): array
{
    $eventId = (int) $row['event_id'];
    $partnerId = (int) $row['partner_id'];
    $type = (string) ($row['match_type'] ?? 'like');
    $room = get_chat_room_for_users($eventId, $userId, $partnerId);

    return [
        'id' => (int) $row['id'],
        'event_id' => $eventId,
        'event_title' => $row['event_title'] ?? null,
        'partner_id' => $partnerId,
        'partner_name' => $row['partner_name'] ?? '',
        'partner_avatar' => !empty($row['partner_avatar']) ? upload_url($row['partner_avatar']) : default_avatar($row['partner_name'] ?? ''),
        'bucket' => $type === 'like' ? 'mutual' : $type,
        'matched_at' => $row['created_at'] ?? null,
        'conversation_id' => $room ? (int) $room['id'] : null,
    ];
}

function mobile_conversation_payload(array $room, int $userId): array
{
    $last = db()->prepare(
        'SELECT body, created_at, sender_id FROM chat_messages WHERE room_id = ? ORDER BY id DESC LIMIT 1'
    );
    $last->execute([(int) $room['id']]);
    $message = $last->fetch() ?: null;

    return [
        'id' => (int) $room['id'],
        'match_id' => (int) $room['id'],
        'event_id' => (int) $room['event_id'],
        'event_title' => $room['event_title'] ?? '',
        'partner_id' => (int) $room['partner_id'],
        'partner_name' => $room['partner_name'] ?? '',
        'partner_avatar' => !empty($room['partner_avatar']) ? upload_url($room['partner_avatar']) : default_avatar($room['partner_name'] ?? ''),
        'last_message' => $message['body'] ?? '',
        'last_message_at' => $message['created_at'] ?? ($room['unlocked_at'] ?? null),
        'unread_count' => 0,
    ];
}

function mobile_message_payload(array $row, int $userId): array
{
    return [
        'id' => (int) $row['id'],
        'conversation_id' => (int) $row['room_id'],
        'sender_id' => (int) $row['sender_id'],
        'body' => (string) ($row['body'] ?? ''),
        'sent_at' => $row['created_at'] ?? null,
        'is_mine' => (int) $row['sender_id'] === $userId,
        'status' => 'sent',
    ];
}

function mobile_registration_payload(array $reg): array
{
    return [
        'event_id' => (int) $reg['event_id'],
        'title' => $reg['title'],
        'slug' => $reg['slug'] ?? null,
        'event_date' => $reg['event_date'],
        'start_time' => $reg['start_time'],
        'location' => $reg['location'] ?? '',
        'city' => $reg['city'] ?? '',
        'ticket_price' => (float) $reg['ticket_price'],
        'registration_status' => $reg['registration_status'],
        'payment_status' => $reg['payment_status'],
        'event_status' => $reg['event_status'] ?? null,
    ];
}

function mobile_notification_payload(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'title' => $row['title'] ?? '',
        'body' => $row['body'] ?? '',
        'type' => $row['type'] ?? 'general',
        'read' => !empty($row['read_at']),
        'created_at' => $row['created_at'],
    ];
}
