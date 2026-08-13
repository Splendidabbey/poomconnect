<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$user = mobile_authenticate_request();
$userId = $user ? (int) $user['id'] : null;
$eventId = (int) ($_GET['id'] ?? 0);
$slug = trim((string) ($_GET['slug'] ?? ''));

if ($eventId <= 0 && $slug === '') {
    mobile_json_response(['success' => false, 'message' => 'Event id or slug required'], 422);
}

$event = $slug !== '' ? get_event_by_slug($slug) : get_event_by_id($eventId);

if (!$event || !in_array($event['status'] ?? '', ['published', 'live'], true)) {
    mobile_json_response(['success' => false, 'message' => 'Event not found'], 404);
}

$orgStmt = db()->prepare('SELECT name FROM organizations WHERE id = ?');
$orgStmt->execute([(int) $event['organization_id']]);
$event['organization_name'] = $orgStmt->fetchColumn() ?: null;

$countStmt = db()->prepare('SELECT COUNT(*) FROM event_participants WHERE event_id = ?');
$countStmt->execute([(int) $event['id']]);
$event['participant_count'] = (int) $countStmt->fetchColumn();

$catName = null;
if (!empty($event['category_id'])) {
    $catStmt = db()->prepare('SELECT name FROM categories WHERE id = ?');
    $catStmt->execute([(int) $event['category_id']]);
    $catName = $catStmt->fetchColumn() ?: null;
}
$event['category_name'] = $catName;

mobile_json_response([
    'success' => true,
    'event' => mobile_event_payload($event, $userId),
]);
