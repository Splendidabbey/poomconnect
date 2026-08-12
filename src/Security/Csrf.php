<?php

declare(strict_types=1);

namespace PoomConnect\Security;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    public const FIELD_NAME = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verify(?string $submitted): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($expected) || !is_string($submitted) || $submitted === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }
}
