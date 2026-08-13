<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$limit = min(50, max(1, (int) ($_GET['limit'] ?? 30)));
$rows = get_user_notifications((int) $user['id'], $limit);
$unread = unread_notification_count((int) $user['id']);

mobile_json_response([
    'success' => true,
    'unread_count' => $unread,
    'notifications' => array_map('mobile_notification_payload', $rows),
]);
