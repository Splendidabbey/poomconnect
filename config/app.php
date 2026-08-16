<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

if (is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

define('APP_NAME', 'Poom Connect');
define('APP_TAGLINE', 'Meet. Connect. Belong.');
define('APP_URL', $_ENV['APP_URL'] ?? ''); // e.g. https://yourdomain.com — leave empty for auto-detect
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

if (($_ENV['APP_ENV'] ?? '') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

date_default_timezone_set('Asia/Bangkok');

if (!defined('SEO_SKIP_SESSION') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_ROOT . '/includes/i18n.php';
init_locale();

require_once APP_ROOT . '/includes/localization.php';
init_currency();

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/seo.php';
require_once APP_ROOT . '/includes/content.php';
require_once APP_ROOT . '/includes/platform.php';
require_once APP_ROOT . '/includes/promptpay.php';
require_once APP_ROOT . '/includes/stripe.php';
require_once APP_ROOT . '/includes/realtime.php';
require_once APP_ROOT . '/includes/roles.php';
require_once APP_ROOT . '/includes/tenant.php';
require_once APP_ROOT . '/includes/subscriptions.php';
require_once APP_ROOT . '/includes/safety.php';
require_once APP_ROOT . '/includes/community.php';
require_once APP_ROOT . '/includes/marketplace.php';
require_once APP_ROOT . '/includes/templates.php';
require_once APP_ROOT . '/includes/ai_policy.php';
require_once APP_ROOT . '/includes/admin-platform.php';
require_once APP_ROOT . '/includes/admin-users.php';
require_once APP_ROOT . '/includes/payment-settings.php';
require_once APP_ROOT . '/includes/mobile-api.php';
require_once APP_ROOT . '/includes/marketing.php';
require_once APP_ROOT . '/includes/social-share.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/security.php';

// Schema is no longer created/altered on every request — see migrate.php.
// Run `php migrate.php` after pulling changes that add new ensure_*_schema()
// logic or files under migrations/.

resolve_tenant_from_request();

function app_web_base(): string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (is_string($docRoot) && $docRoot !== '') {
        $docRootReal = realpath($docRoot);
        $appRootReal = realpath(APP_ROOT) ?: APP_ROOT;
        $docRootNorm = rtrim(str_replace('\\', '/', $docRootReal !== false ? $docRootReal : $docRoot), '/');
        $appRootNorm = rtrim(str_replace('\\', '/', $appRootReal), '/');

        if ($docRootNorm !== '' && str_starts_with($appRootNorm, $docRootNorm)) {
            $base = substr($appRootNorm, strlen($docRootNorm));

            return ($base === '/' || $base === '') ? '' : $base;
        }
    }

    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = rtrim($script, '/');
    $known = ['/api/mobile', '/organizer', '/admin', '/api', '/community', '/participant', '/org', '/safety', '/cron'];

    foreach ($known as $suffix) {
        if (str_ends_with($base, $suffix)) {
            $base = substr($base, 0, -strlen($suffix));
            break;
        }
    }

    return ($base === '/' || $base === '\\' || $base === '.' || $base === '') ? '' : $base;
}

function base_url(string $path = ''): string
{
    if (APP_URL !== '') {
        $origin = rtrim(APP_URL, '/');
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $origin = $scheme . '://' . $host . app_web_base();
    }

    $pretty = public_url_path($path);

    if ($pretty === '' || str_starts_with($pretty, '?') || str_starts_with($pretty, '#')) {
        return $origin . $pretty;
    }

    return $origin . '/' . ltrim($pretty, '/');
}

function asset_url(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function upload_url(string $path): string
{
    return base_url('uploads/' . ltrim($path, '/'));
}

function brand_url(string $path): string
{
    return base_url('poomconnect_images/' . ltrim($path, '/'));
}

function brand_favicon(): string
{
    return brand_url('favicon/favicon-48.png');
}

function brand_logo(string $size = 'md'): string
{
    return match ($size) {
        'nav' => brand_url('websites-logo/poom-logo-nav.svg'),
        'sm' => brand_url('websites-logo/poom-logo-200x50.png'),
        'lg' => brand_url('websites-logo/poom-logo-320x80.png'),
        default => brand_url('websites-logo/poom-logo-240x60.png'),
    };
}

function brand_app_icon(string $size = '512'): string
{
    return brand_url($size === '1024' ? 'app-icons/icon-1024.png' : 'app-icons/icon-512.png');
}
