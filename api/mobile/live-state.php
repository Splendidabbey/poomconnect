<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$eventId = (int) ($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
    mobile_json_response(['success' => false, 'message' => 'Event id required'], 422);
}

$reg = get_user_event_registration($eventId, (int) $user['id']);
if (!$reg || $reg['payment_status'] !== 'approved') {
    mobile_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

mobile_json_response([
    'success' => true,
    'live' => get_live_state_payload($eventId, (int) $user['id']),
]);
