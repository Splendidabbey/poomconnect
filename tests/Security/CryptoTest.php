<?php

declare(strict_types=1);

namespace PoomConnect\Tests\Security;

use PoomConnect\Security\Crypto;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CryptoTest extends TestCase
{
    public function testEncryptDecryptRoundTrip(): void
    {
        $plaintext = 'sk_live_super_secret_stripe_key';
        $encrypted = Crypto::encrypt($plaintext);

        $this->assertNotSame($plaintext, $encrypted);
        $this->assertSame($plaintext, Crypto::decrypt($encrypted));
    }

    public function testEncryptProducesDifferentCiphertextEachTime(): void
    {
        $plaintext = 'same-secret';

        $this->assertNotSame(Crypto::encrypt($plaintext), Crypto::encrypt($plaintext));
    }

    public function testWrapMarksValueAsEncrypted(): void
    {
        $wrapped = Crypto::wrap('my-secret');

        $this->assertTrue(Crypto::isEncrypted($wrapped));
        $this->assertFalse(Crypto::isEncrypted('plain-legacy-value'));
    }

    public function testUnwrapReturnsPlaintextValueUnchanged(): void
    {
        // Legacy plaintext values (saved before encryption existed) must pass through untouched.
        $this->assertSame('legacy-plaintext', Crypto::unwrap('legacy-plaintext'));
    }

    public function testUnwrapDecryptsWrappedValue(): void
    {
        $wrapped = Crypto::wrap('my-secret');

        $this->assertSame('my-secret', Crypto::unwrap($wrapped));
    }

    public function testDecryptRejectsTamperedPayload(): void
    {
        $encrypted = Crypto::encrypt('tamper-test');
        $tampered = substr($encrypted, 0, -4) . 'abcd';

        $this->expectException(RuntimeException::class);
        Crypto::decrypt($tampered);
    }

    public function testDecryptRejectsGarbageInput(): void
    {
        $this->expectException(RuntimeException::class);
        Crypto::decrypt('not-valid-base64-cipher-text');
    }
}
