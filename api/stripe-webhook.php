<?php

declare(strict_types=1);

/**
 * Stripe webhook receiver. Authenticated via Stripe-Signature (not a user
 * session), so this deliberately skips the CSRF/auth conventions used by the
 * rest of api/*.php.
 */

require_once __DIR__ . '/_bootstrap.php';

$payload = (string) file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhookSecret = stripe_webhook_secret();

if ($webhookSecret === '') {
    error_log('Stripe webhook received but no webhook secret is configured.');
    json_response(['success' => false], 500);
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
} catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
    error_log('Stripe webhook signature verification failed: ' . $e->getMessage());
    json_response(['success' => false], 400);
}

$dedupe = db()->prepare('INSERT IGNORE INTO payment_webhook_events (gateway, event_id) VALUES (?, ?)');
$dedupe->execute(['stripe', $event->id]);

if ($dedupe->rowCount() === 0) {
    // Already processed this event id (retried delivery) — ack without reprocessing.
    json_response(['success' => true, 'duplicate' => true]);
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $paymentId = (int) ($session->client_reference_id ?? 0);

    if ($paymentId > 0) {
        approve_payment($paymentId, null);
    } else {
        error_log('Stripe webhook: checkout.session.completed missing client_reference_id.');
    }
}

json_response(['success' => true]);
