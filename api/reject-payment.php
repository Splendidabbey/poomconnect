<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_api_organizer();

$paymentId = (int) ($_POST['payment_id'] ?? 0);
$payment = get_payment_for_organizer($paymentId);

if (!$payment) {
    json_response(['success' => false, 'message' => __('api.payment_not_found')]);
}

if (reject_payment($paymentId, (int) current_user()['id'])) {
    log_admin_action((int) current_user()['id'], 'payment_rejected', 'Payment ID: ' . $paymentId);
    json_response(['success' => true, 'message' => __('api.payment_rejected')]);
}

json_response(['success' => false, 'message' => __('api.reject_failed')]);
