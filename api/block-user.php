<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/api/_bootstrap.php';
require_login(['participant', 'organizer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => __('api.invalid_request')], 405);
}

$blockerId = (int) current_user()['id'];
$blockedId = (int) ($_POST['blocked_id'] ?? 0);
$action = $_POST['action'] ?? 'block';

if (!$blockedId) {
    json_response(['success' => false, 'message' => __('api.invalid_request')], 400);
}

if ($action === 'unblock') {
    unblock_user($blockerId, $blockedId);
} else {
    block_user($blockerId, $blockedId);
}

json_response(['success' => true, 'message' => __('safety.block_updated')]);
