<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$eventId = (int) ($_POST['event_id'] ?? 0);
$userId = (int) ($_POST['user_id'] ?? 0);
$event = get_event_for_organizer($eventId);

if (!$event || !$userId) {
    json_response(['success' => false, 'message' => 'Invalid request.']);
}

$token = generate_ticket($eventId, $userId);

if ($token) {
    json_response(['success' => true, 'message' => 'Ticket generated.', 'qr_token' => $token]);
}

json_response(['success' => false, 'message' => 'Could not generate ticket.']);
