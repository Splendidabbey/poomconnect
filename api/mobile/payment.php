<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = array_merge($_POST, mobile_read_json_body());

$eventId = (int) ($body['event_id'] ?? $_GET['event_id'] ?? 0);
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

function mobile_payment_payload(array $event, array $reg, ?array $payment, array $user): array
{
    $details = resolve_event_payment_details($event);
    $amount = (float) ($payment['amount'] ?? $event['ticket_price']);
    $promptpayId = $details['promptpay_enabled'] ? ($details['promptpay_number'] ?? '') : '';
    $qrUrl = $promptpayId !== '' ? promptpay_qr_url($promptpayId, $amount) : null;
    $slipUploaded = !empty($payment['slip_image']);
    $status = $reg['payment_status'] ?? 'pending';
    if ($status === 'pending' && $slipUploaded) {
        $status = 'submitted';
    }

    return [
        'id' => $payment ? (int) $payment['id'] : 0,
        'event_id' => (int) $event['id'],
        'member_id' => (int) $user['id'],
        'member_name' => $user['full_name'] ?? '',
        'amount' => $amount,
        'currency' => current_currency(),
        'status' => $status,
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
        'slip_uploaded' => $slipUploaded,
        'slip_image' => $slipUploaded ? upload_url($payment['slip_image']) : null,
        'submitted_at' => $payment['created_at'] ?? null,
    ];
}

$paymentStmt = db()->prepare('SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$eventId, (int) $user['id']]);
$payment = $paymentStmt->fetch() ?: null;

if ($method === 'GET') {
    mobile_json_response([
        'success' => true,
        'payment' => mobile_payment_payload($event, $reg, $payment, $user),
    ]);
}

if ($method === 'POST') {
    if (rate_limit_exceeded('mobile_slip_upload', client_ip(), 10, 300)) {
        mobile_json_response(['success' => false, 'message' => 'Too many attempts. Try again later.'], 429);
    }

    if (!isset($_FILES['slip_image'])) {
        mobile_json_response(['success' => false, 'message' => 'Upload a PromptPay slip'], 422);
    }

    rate_limit_hit('mobile_slip_upload', client_ip());
    $path = save_upload($_FILES['slip_image'], 'slips', 'slip');
    if (!$path) {
        mobile_json_response(['success' => false, 'message' => 'Invalid file'], 422);
    }

    $amount = (float) ($payment['amount'] ?? $event['ticket_price']);
    $methodSlug = get_default_payment_method_slug();

    if ($payment) {
        db()->prepare('UPDATE payments SET slip_image = ?, payment_status = ? WHERE id = ?')
            ->execute([$path, 'pending', $payment['id']]);
    } else {
        db()->prepare(
            'INSERT INTO payments (event_id, user_id, amount, payment_method, payment_status, slip_image, original_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$eventId, (int) $user['id'], $amount, $methodSlug, 'pending', $path, $amount]);
    }

    $paymentStmt->execute([$eventId, (int) $user['id']]);
    $payment = $paymentStmt->fetch() ?: $payment;

    mobile_json_response([
        'success' => true,
        'payment' => mobile_payment_payload($event, $reg, $payment, $user),
        'message' => 'Slip sent. The host will approve your ticket.',
    ]);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
