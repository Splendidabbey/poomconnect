<?php

declare(strict_types=1);

namespace PoomConnect\Tests;

use PHPUnit\Framework\TestCase;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Exercises the exact signature-verification call api/stripe-webhook.php makes
 * (\Stripe\Webhook::constructEvent), without any live Stripe keys or network
 * access — the signature scheme (HMAC-SHA256 over "{timestamp}.{payload}") is
 * public and reproducible.
 */
final class StripeWebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_secret_12345';

    public function testValidSignatureIsAccepted(): void
    {
        $payload = $this->samplePayload();
        $header = $this->signedHeader($payload, self::SECRET);

        $event = Webhook::constructEvent($payload, $header, self::SECRET);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('checkout.session.completed', $event->type);
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $payload = $this->samplePayload();
        $header = $this->signedHeader($payload, self::SECRET);
        $tamperedPayload = str_replace('"payment_id":"42"', '"payment_id":"999"', $payload);

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($tamperedPayload, $header, self::SECRET);
    }

    public function testWrongSecretIsRejected(): void
    {
        $payload = $this->samplePayload();
        $header = $this->signedHeader($payload, self::SECRET);

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($payload, $header, 'whsec_wrong_secret');
    }

    public function testMissingSignatureHeaderIsRejected(): void
    {
        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($this->samplePayload(), '', self::SECRET);
    }

    private function signedHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return "t={$timestamp},v1={$signature}";
    }

    private function samplePayload(): string
    {
        return json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'client_reference_id' => '42',
                    'metadata' => ['payment_id' => '42'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
