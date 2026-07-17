<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$eventId = (int) ($_POST['event_id'] ?? 0);
$event = get_event_for_organizer($eventId);

if (!$event) {
    json_response(['success' => false, 'message' => __('api.event_not_found')]);
}

$participants = get_checked_in_participants($eventId);

if (count($participants) < 2) {
    json_response(['success' => false, 'message' => __('api.need_participants')]);
}

ensure_live_state($eventId, (int) $event['round_duration']);

$pairs = generate_ai_round_pairings($eventId, 1);

$pdo = db();
$pdo->beginTransaction();

try {
    if ($pairs !== []) {
        save_round_pairings($eventId, $pairs);
    }

    $update = $pdo->prepare(
        'UPDATE live_event_state SET current_round = 1, event_status = ?, timer_seconds = ?, timer_started_at = NOW(), updated_at = NOW() WHERE event_id = ?'
    );
    $update->execute(['live', (int) $event['round_duration'], $eventId]);

    $eventUpdate = $pdo->prepare("UPDATE events SET status = 'live' WHERE id = ?");
    $eventUpdate->execute([$eventId]);

    $pdo->commit();

    log_admin_action((int) current_user()['id'], 'event_started', 'Event ID: ' . $eventId);

    json_response(['success' => true, 'message' => __('api.event_started', ['count' => count($pairs)])]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Start event failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => __('api.event_start_failed')]);
}
