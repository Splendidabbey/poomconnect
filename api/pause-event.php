<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$eventId = (int) ($_POST['event_id'] ?? 0);
$event = get_event_for_organizer($eventId);

if (!$event) {
    json_response(['success' => false, 'message' => __('api.event_not_found')]);
}

ensure_live_state($eventId, (int) $event['round_duration']);

$update = db()->prepare(
    'UPDATE live_event_state SET event_status = ?, updated_at = NOW() WHERE event_id = ?'
);
$update->execute(['paused', $eventId]);

db()->prepare("UPDATE events SET status = 'paused' WHERE id = ?")->execute([$eventId]);

json_response(['success' => true, 'message' => __('api.event_paused')]);
