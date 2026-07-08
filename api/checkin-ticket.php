<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$qrToken = trim($_POST['qr_token'] ?? '');

if ($qrToken === '') {
    json_response(['success' => false, 'message' => 'QR token required.']);
}

$result = checkin_ticket($qrToken);

json_response([
    'success' => $result['success'],
    'warning' => $result['warning'] ?? false,
    'message' => strip_tags($result['message']),
]);
