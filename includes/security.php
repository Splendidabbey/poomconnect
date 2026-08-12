<?php

declare(strict_types=1);

use PoomConnect\Security\Crypto;
use PoomConnect\Security\Csrf;
use PoomConnect\Security\RateLimiter;

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="' . Csrf::FIELD_NAME . '" value="' . e(csrf_token()) . '">';
}

/** Plain boolean check against $_POST['_csrf'], for handlers needing custom failure behavior. */
function csrf_verify(): bool
{
    return Csrf::verify($_POST[Csrf::FIELD_NAME] ?? null);
}

/**
 * Verifies the CSRF token on the current POST request. On failure, sets a flash
 * error and redirects back to the current page (mirrors this codebase's existing
 * set_flash()/redirect() error pattern instead of a hard 403). Call this as the
 * first line inside `if ($_SERVER['REQUEST_METHOD'] === 'POST') { ... }`.
 */
function csrf_verify_or_redirect(): void
{
    if (!Csrf::verify($_POST[Csrf::FIELD_NAME] ?? null)) {
        set_flash('error', __('validation.csrf_invalid'));
        redirect($_SERVER['REQUEST_URI'] ?? base_url());
    }
}

/** For api/*.php JSON endpoints: verifies CSRF on POST, or emits a 419 JSON error. */
function csrf_verify_or_json(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!Csrf::verify($_POST[Csrf::FIELD_NAME] ?? null)) {
        json_response(['success' => false, 'message' => __('api.csrf_invalid')], 419);
    }
}

function encrypt_secret(string $plaintext): string
{
    return Crypto::wrap($plaintext);
}

function decrypt_secret(string $value): string
{
    return Crypto::unwrap($value);
}

function rate_limit_exceeded(string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool
{
    return RateLimiter::tooManyAttempts(db(), $bucket, $key, $maxAttempts, $windowSeconds);
}

function rate_limit_hit(string $bucket, string $key): void
{
    RateLimiter::hit(db(), $bucket, $key);
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
