<?php

declare(strict_types=1);

namespace PoomConnect\Security;

use PDO;

/**
 * Fixed-window rate limiter backed by the rate_limit_hits table (see migrations/0017).
 * Not for high-traffic distributed systems — adequate for a single-server MVP.
 */
final class RateLimiter
{
    public static function tooManyAttempts(PDO $pdo, string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool
    {
        self::prune($pdo, $bucket, $windowSeconds);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM rate_limit_hits
             WHERE bucket = ? AND rate_key = ? AND created_at >= (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$bucket, $key, $windowSeconds]);

        return (int) $stmt->fetchColumn() >= $maxAttempts;
    }

    public static function hit(PDO $pdo, string $bucket, string $key): void
    {
        $stmt = $pdo->prepare('INSERT INTO rate_limit_hits (bucket, rate_key, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$bucket, $key]);
    }

    private static function prune(PDO $pdo, string $bucket, int $windowSeconds): void
    {
        $stmt = $pdo->prepare(
            'DELETE FROM rate_limit_hits WHERE bucket = ? AND created_at < (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$bucket, $windowSeconds * 4]);
    }
}
