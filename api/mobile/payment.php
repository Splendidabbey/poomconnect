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

$event = get_event_by_id($eventId);
if (!$event) {
    mobile_json_response(['success' => false, 'message' => 'Event not found'], 404);
}

$reg = get_user_event_registration($eventId, (int) $user['id']);
if (!$reg || $reg['registration_status'] === 'waitlist') {
    mobile_json_response(['success' => false, 'message' => 'Registration required'], 403);
}

$paymentStmt = db()->prepare('SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$eventId, (int) $user['id']]);
$payment = $paymentStmt->fetch();

$details = resolve_event_payment_details($event);
$amount = (float) ($payment['amount'] ?? $event['ticket_price']);

$promptpayId = $details['promptpay_enabled'] ? ($details['promptpay_number'] ?? '') : '';
$qrUrl = $promptpayId !== '' ? promptpay_qr_url($promptpayId, $amount) : null;

mobile_json_response([
    'success' => true,
    'payment' => [
        'amount' => $amount,
        'currency' => current_currency(),
        'status' => $reg['payment_status'],
        'method' => $payment['payment_method'] ?? get_default_payment_method_slug(),
        'promptpay_number' => $promptpayId !== '' ? $promptpayId : null,
        'promptpay_qr_url' => $qrUrl,
        'bank' => $details['bank_transfer_enabled'] && ($details['bank_name'] ?? '') !== '' ? [
            'name' => $details['bank_name'],
            'account_name' => $details['bank_account_name'],
            'account_number' => $details['bank_account_number'],
        ] : null,
        'instructions' => $details['instructions'] !== '' ? $details['instructions'] : null,
        'slip_upload_required' => $details['slip_upload_required'],
        'slip_uploaded' => !empty($payment['slip_image']),
    ],
]);
