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
    mobile_json_response(['success' => false, 'message' => 'Ticket not available'], 403);
}

$stmt = db()->prepare(
    'SELECT t.*, e.title AS event_title, e.event_date, e.start_time, e.location
     FROM tickets t
     JOIN events e ON e.id = t.event_id
     WHERE t.event_id = ? AND t.user_id = ? LIMIT 1'
);
$stmt->execute([$eventId, (int) $user['id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    mobile_json_response(['success' => false, 'message' => 'Ticket not found'], 404);
}

mobile_json_response([
    'success' => true,
    'ticket' => [
        'id' => (int) $ticket['id'],
        'event_id' => (int) $ticket['event_id'],
        'member_id' => (int) $ticket['user_id'],
        'event_title' => $ticket['event_title'],
        'event_date' => $ticket['event_date'],
        'start_time' => $ticket['start_time'],
        'location' => $ticket['location'] ?? '',
        'participant_name' => $user['full_name'],
        'code' => $ticket['qr_token'],
        'qr_token' => $ticket['qr_token'],
        'status' => !empty($ticket['checked_in']) ? 'used' : 'valid',
        'issued_at' => $ticket['created_at'] ?? null,
        'checked_in' => (bool) $ticket['checked_in'],
    ],
]);
