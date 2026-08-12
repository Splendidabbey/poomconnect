<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
csrf_verify_or_json();
require_api_organizer();

$eventId = (int) ($_POST['event_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$event = get_event_for_organizer($eventId);

if (!$event || $message === '') {
    json_response(['success' => false, 'message' => __('api.invalid_request')]);
}

send_event_broadcast($eventId, (int) current_user()['id'], $message);
json_response(['success' => true, 'message' => __('realtime.broadcast_sent')]);
