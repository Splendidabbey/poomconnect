<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    mobile_json_response(['success' => true, 'user' => mobile_user_payload($user)]);
}

if ($method === 'PUT' || $method === 'POST') {
    $body = mobile_read_json_body();
    $fullName = trim((string) ($body['full_name'] ?? ''));
    $phone = trim((string) ($body['phone'] ?? ''));
    $city = trim((string) ($body['city'] ?? ''));
    $bio = trim((string) ($body['bio'] ?? ''));

    if ($fullName === '') {
        mobile_json_response(['success' => false, 'message' => 'Full name is required'], 422);
    }

    db()->prepare('UPDATE users SET full_name = ?, phone = ?, city = ?, bio = ? WHERE id = ?')
        ->execute([$fullName, $phone ?: null, $city ?: null, $bio ?: null, (int) $user['id']]);

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $user['id']]);
    $updated = $stmt->fetch() ?: $user;

    mobile_json_response(['success' => true, 'user' => mobile_user_payload($updated)]);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
