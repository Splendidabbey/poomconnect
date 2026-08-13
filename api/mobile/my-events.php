<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $registrations = get_user_registrations((int) $user['id']);
    mobile_json_response([
        'success' => true,
        'registrations' => array_map('mobile_registration_payload', $registrations),
    ]);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
