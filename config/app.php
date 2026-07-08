<?php

declare(strict_types=1);

define('APP_NAME', 'Poom Connect');
define('APP_TAGLINE', 'Meet. Connect. Belong.');
define('APP_URL', ''); // e.g. https://yourdomain.com — leave empty for auto-detect
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

function base_url(string $path = ''): string
{
    if (APP_URL !== '') {
        return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(str_replace('\\', '/', $script), '/');

    if (str_ends_with($base, '/organizer') || str_ends_with($base, '/admin') || str_ends_with($base, '/api')) {
        $base = dirname($base);
    }

    $url = $scheme . '://' . $host . ($base === '/' ? '' : $base);

    if ($path === '') {
        return $url;
    }

    return $url . '/' . ltrim($path, '/');
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
        'sm' => brand_url('websites-logo/poom-logo-200x50.png'),
        'lg' => brand_url('websites-logo/poom-logo-320x80.png'),
        default => brand_url('websites-logo/poom-logo-240x60.png'),
    };
}

function brand_app_icon(string $size = '512'): string
{
    return brand_url($size === '1024' ? 'app-icons/icon-1024.png' : 'app-icons/icon-512.png');
}
