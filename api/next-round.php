<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$eventId = (int) ($_POST['event_id'] ?? 0);
$event = get_event_for_organizer($eventId);

if (!$event) {
    json_response(['success' => false, 'message' => __('api.event_not_found')]);
}

$liveState = get_live_state($eventId);
if (!$liveState) {
    ensure_live_state($eventId, (int) $event['round_duration']);
    $liveState = get_live_state($eventId);
}

$nextRound = (int) $liveState['current_round'] + 1;
$participants = get_checked_in_participants($eventId);

if (count($participants) < 2) {
    json_response(['success' => false, 'message' => __('api.need_participants_round')]);
}

$pairs = generate_ai_round_pairings($eventId, $nextRound);

if ($pairs === []) {
    json_response(['success' => false, 'message' => __('api.no_pairings')]);
}

$pdo = db();
$pdo->beginTransaction();

try {
    save_round_pairings($eventId, $pairs);

    $update = $pdo->prepare(
        'UPDATE live_event_state SET current_round = ?, event_status = ?, timer_seconds = ?, timer_started_at = NOW(), updated_at = NOW() WHERE event_id = ?'
    );
    $update->execute([$nextRound, 'live', (int) $event['round_duration'], $eventId]);

    $eventUpdate = $pdo->prepare("UPDATE events SET status = 'live' WHERE id = ?");
    $eventUpdate->execute([$eventId]);

    $pdo->commit();

    json_response(['success' => true, 'message' => __('api.round_started', ['round' => $nextRound, 'count' => count($pairs)])]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Next round failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => __('api.round_failed')]);
}
