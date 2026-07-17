<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/api/_bootstrap.php';
require_login(['participant', 'organizer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => __('api.invalid_request')], 405);
}

$userId = (int) current_user()['id'];
$reportedId = (int) ($_POST['reported_id'] ?? 0);
$reason = $_POST['reason'] ?? 'other';

if (!$reportedId || !in_array($reason, safety_report_reasons(), true)) {
    json_response(['success' => false, 'message' => __('api.invalid_request')], 400);
}

report_user($userId, $reportedId, $reason, trim($_POST['details'] ?? ''), (int) ($_POST['event_id'] ?? 0) ?: null);
json_response(['success' => true, 'message' => __('safety.report_sent')]);
