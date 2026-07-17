<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$entityType = $_POST['entity_type'] ?? '';
$entityId = (int) ($_POST['entity_id'] ?? 0);
$channel = $_POST['channel'] ?? '';

if (!in_array($entityType, ['event', 'blog', 'org', 'referral'], true) || $entityId <= 0 || $channel === '') {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

$userId = is_logged_in() ? (int) current_user()['id'] : null;
log_social_share($entityType, $entityId, $channel, $userId);

json_response(['success' => true]);
