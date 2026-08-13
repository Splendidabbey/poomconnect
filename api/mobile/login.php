<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = array_merge($_POST, mobile_read_json_body());
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');
$deviceName = trim((string) ($body['device_name'] ?? 'Poom Connect App'));

if ($email === '' || $password === '') {
    mobile_json_response(['success' => false, 'message' => 'Email and password are required'], 422);
}

if (rate_limit_exceeded('mobile_login', client_ip(), 20, 300)) {
    mobile_json_response(['success' => false, 'message' => 'Too many attempts. Try again later.'], 429);
}

if (!login_participant($email, $password) && !login_user($email, $password, true)) {
    rate_limit_hit('mobile_login', client_ip());
    mobile_json_response(['success' => false, 'message' => 'Invalid email or password'], 401);
}

$user = current_user();
if (!$user) {
    mobile_json_response(['success' => false, 'message' => 'Login failed'], 500);
}

$token = mobile_create_token((int) $user['id'], $deviceName !== '' ? $deviceName : null);

mobile_json_response([
    'success' => true,
    'token' => $token,
    'user' => mobile_user_payload($user),
]);
