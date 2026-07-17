<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$eventId = (int) ($_GET['event_id'] ?? 0);
$userId = (int) ($_SESSION['participant_user_id'] ?? current_user_id() ?? 0);

if (!$eventId || !$userId) {
    json_response(['success' => false, 'message' => __('api.unauthorized')], 401);
}

$reg = get_user_event_registration($eventId, $userId);
if (!$reg || $reg['payment_status'] !== 'approved') {
    json_response(['success' => false, 'message' => __('api.unauthorized')], 403);
}

json_response(['success' => true, 'live' => get_live_state_payload($eventId, $userId)]);
