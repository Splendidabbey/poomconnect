<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = mobile_read_json_body();

if ($method === 'POST') {
    $eventId = (int) ($body['event_id'] ?? $_POST['event_id'] ?? 0);
    $couponCode = trim((string) ($body['coupon_code'] ?? ''));

    if ($eventId <= 0) {
        mobile_json_response(['success' => false, 'message' => 'Event id required'], 422);
    }

    $event = get_event_by_id($eventId);
    if (!$event) {
        mobile_json_response(['success' => false, 'message' => 'Event not found'], 404);
    }

    $couponId = null;
    if ($couponCode !== '') {
        $coupon = get_coupon_by_code($couponCode, (int) $event['organization_id']);
        if (!$coupon || !coupon_valid($coupon, $eventId)) {
            mobile_json_response(['success' => false, 'message' => 'Invalid coupon code'], 422);
        }
        $couponId = (int) $coupon['id'];
    }

    $result = join_event($eventId, (int) $user['id'], (float) $event['ticket_price'], $couponId);
    if (!$result['ok']) {
        mobile_json_response(['success' => false, 'message' => $result['error'] ?? 'Registration failed'], 422);
    }

    mobile_json_response([
        'success' => true,
        'waitlist' => (bool) ($result['waitlist'] ?? false),
        'free' => (bool) ($result['free'] ?? false),
        'message' => ($result['waitlist'] ?? false) ? 'Added to waitlist' : 'Registered successfully',
    ], 201);
}

if ($method === 'DELETE') {
    $eventId = (int) ($body['event_id'] ?? $_GET['event_id'] ?? 0);
    if ($eventId <= 0) {
        mobile_json_response(['success' => false, 'message' => 'Event id required'], 422);
    }

    if (!cancel_event_registration($eventId, (int) $user['id'])) {
        mobile_json_response(['success' => false, 'message' => 'Could not cancel registration'], 422);
    }

    mobile_json_response(['success' => true, 'message' => 'Registration cancelled']);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
