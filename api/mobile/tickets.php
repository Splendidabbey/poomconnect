<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$stmt = db()->prepare(
    'SELECT t.*, e.title AS event_title, e.event_date, e.start_time, e.location, e.city, e.status AS event_status
     FROM tickets t
     JOIN events e ON e.id = t.event_id
     WHERE t.user_id = ?
     ORDER BY e.event_date ASC, e.start_time ASC'
);
$stmt->execute([(int) $user['id']]);
$rows = $stmt->fetchAll();

mobile_json_response([
    'success' => true,
    'tickets' => array_map(
        static function (array $ticket) use ($user): array {
            return [
                'id' => (int) $ticket['id'],
                'event_id' => (int) $ticket['event_id'],
                'event_title' => $ticket['event_title'],
                'event_date' => $ticket['event_date'],
                'start_time' => $ticket['start_time'],
                'location' => $ticket['location'] ?? '',
                'city' => $ticket['city'] ?? '',
                'event_status' => $ticket['event_status'] ?? null,
                'member_id' => (int) $user['id'],
                'code' => $ticket['qr_token'],
                'status' => !empty($ticket['checked_in']) ? 'used' : 'valid',
                'issued_at' => $ticket['created_at'] ?? null,
            ];
        },
        $rows
    ),
]);
