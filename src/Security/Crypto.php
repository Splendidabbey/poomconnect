<?php

declare(strict_types=1);

namespace PoomConnect\Security;

use RuntimeException;

/**
 * Symmetric encryption for at-rest secrets (payment gateway credentials, etc).
 * Uses libsodium secretbox (XSalsa20-Poly1305) keyed by APP_ENCRYPTION_KEY.
 */
final class Crypto
{
    public static function encrypt(string $plaintext): string
    {
        $key = self::key();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return base64_encode($nonce . $cipher);
    }

    public static function decrypt(string $encoded): string
    {
        $key = self::key();
        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Invalid encrypted payload.');
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open($cipher, $nonce, $key);

        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt payload (wrong key or corrupted data).');
        }

        return $plaintext;
    }

    /** Marker prefix so callers can distinguish already-encrypted values from legacy plaintext. */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public static function wrap(string $plaintext): string
    {
        return self::PREFIX . self::encrypt($plaintext);
    }

    public static function unwrap(string $value): string
    {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        return self::decrypt(substr($value, strlen(self::PREFIX)));
    }

    private const PREFIX = 'enc:v1:';

    private static function key(): string
    {
        $encoded = $_ENV['APP_ENCRYPTION_KEY'] ?? getenv('APP_ENCRYPTION_KEY') ?: '';

        if ($encoded === '') {
            throw new RuntimeException('APP_ENCRYPTION_KEY is not set. Generate one and add it to .env.');
        }

        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
        }

        return $key;
    }
}
