<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = mobile_read_json_body();
$eventId = (int) ($body['event_id'] ?? 0);
$roundId = (int) ($body['round_id'] ?? 0);
$targetId = (int) ($body['target_id'] ?? 0);
$vote = trim((string) ($body['vote'] ?? ''));

if ($eventId <= 0 || $roundId <= 0 || $targetId <= 0) {
    mobile_json_response(['success' => false, 'message' => 'Missing vote parameters'], 422);
}

if (!in_array($vote, ['like', 'friend', 'business', 'pass'], true)) {
    mobile_json_response(['success' => false, 'message' => 'Invalid vote type'], 422);
}

$reg = get_user_event_registration($eventId, (int) $user['id']);
if (!$reg || $reg['payment_status'] !== 'approved') {
    mobile_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

process_match_votes($eventId, $roundId, (int) $user['id'], $targetId, $vote);

mobile_json_response(['success' => true, 'message' => 'Vote saved']);
