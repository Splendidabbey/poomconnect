<?php

declare(strict_types=1);

const STRIPE_CHARGE_CURRENCY = 'thb';

function stripe_client(): \Stripe\StripeClient
{
    $gateway = get_payment_gateway('stripe');
    $secretKey = (string) ($gateway['config']['secret_key'] ?? '');

    if ($secretKey === '') {
        throw new RuntimeException('Stripe secret key is not configured.');
    }

    return new \Stripe\StripeClient($secretKey);
}

function stripe_webhook_secret(): string
{
    $gateway = get_payment_gateway('stripe');

    return (string) ($gateway['config']['webhook_secret'] ?? '');
}

/**
 * Creates a hosted Stripe Checkout Session for a single event payment.
 * Always charges in THB — payments.amount / events.ticket_price are stored in
 * THB regardless of the buyer's display currency (see format_currency()).
 */
function create_stripe_checkout_session(array $event, array $payment): \Stripe\Checkout\Session
{
    $amount = (float) $payment['amount'];

    return stripe_client()->checkout->sessions->create([
        'mode' => 'payment',
        'client_reference_id' => (string) $payment['id'],
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => STRIPE_CHARGE_CURRENCY,
                'unit_amount' => (int) round($amount * 100),
                'product_data' => [
                    'name' => (string) $event['title'],
                ],
            ],
        ]],
        'metadata' => [
            'payment_id' => (string) $payment['id'],
            'event_id' => (string) $event['id'],
            'user_id' => (string) $payment['user_id'],
        ],
        'success_url' => base_url('pay.php?event_id=' . $event['id'] . '&stripe=success'),
        'cancel_url' => base_url('pay.php?event_id=' . $event['id'] . '&stripe=cancelled'),
    ]);
}
