<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$user = mobile_authenticate_request();
$userId = $user ? (int) $user['id'] : null;

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category_id' => (int) ($_GET['category_id'] ?? 0) ?: null,
    'city' => trim((string) ($_GET['city'] ?? '')),
    'event_type' => trim((string) ($_GET['event_type'] ?? '')),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'availability' => trim((string) ($_GET['availability'] ?? '')),
];

$limit = min(100, max(1, (int) ($_GET['limit'] ?? 30)));
$events = search_events($filters, $limit);

mobile_json_response([
    'success' => true,
    'events' => array_map(
        static fn(array $event): array => mobile_event_payload($event, $userId),
        $events
    ),
    'cities' => get_event_cities(),
]);
