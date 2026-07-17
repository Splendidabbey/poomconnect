<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$eventId = (int) ($_POST['event_id'] ?? 0);
$event = get_event_for_organizer($eventId);

if (!$event) {
    json_response(['success' => false, 'message' => __('api.event_not_found')]);
}

emergency_stop_event($eventId);
json_response(['success' => true, 'message' => __('realtime.emergency_stopped')]);
