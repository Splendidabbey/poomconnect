<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = mobile_read_json_body();
$fullName = trim((string) ($body['full_name'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');
$confirm = (string) ($body['password_confirm'] ?? $password);
$deviceName = trim((string) ($body['device_name'] ?? 'Poom Connect App'));

if ($fullName === '' || $email === '' || $password === '') {
    mobile_json_response(['success' => false, 'message' => 'Full name, email, and password are required'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    mobile_json_response(['success' => false, 'message' => 'Invalid email address'], 422);
}

if (strlen($password) < 6) {
    mobile_json_response(['success' => false, 'message' => 'Password must be at least 6 characters'], 422);
}

if ($password !== $confirm) {
    mobile_json_response(['success' => false, 'message' => 'Passwords do not match'], 422);
}

if (rate_limit_exceeded('mobile_signup', client_ip(), 10, 300)) {
    mobile_json_response(['success' => false, 'message' => 'Too many attempts. Try again later.'], 429);
}

$result = register_account($fullName, $email, $password, 'participant');
if (!$result['ok']) {
    mobile_json_response(['success' => false, 'message' => $result['error'] ?? 'Registration failed'], 422);
}

rate_limit_hit('mobile_signup', client_ip());

if (!login_participant($email, $password)) {
    mobile_json_response(['success' => false, 'message' => 'Account created but login failed'], 500);
}

$user = current_user();
$token = mobile_create_token((int) $user['id'], $deviceName !== '' ? $deviceName : null);

mobile_json_response([
    'success' => true,
    'token' => $token,
    'user' => mobile_user_payload($user),
], 201);
