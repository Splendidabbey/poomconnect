<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $user = mobile_require_auth();
    mobile_json_response(['success' => true, 'user' => mobile_user_payload($user)]);
}

if ($method === 'POST') {
    $token = mobile_bearer_token();
    if ($token) {
        mobile_revoke_token($token);
    }
    mobile_json_response(['success' => true, 'message' => 'Logged out']);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
