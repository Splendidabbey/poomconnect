<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$userId = (int) $user['id'];
$rows = get_user_all_matches($userId);

mobile_json_response([
    'success' => true,
    'matches' => array_map(
        static fn(array $row): array => mobile_match_payload($row, $userId),
        $rows
    ),
]);
