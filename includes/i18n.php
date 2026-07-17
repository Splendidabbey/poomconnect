<?php

declare(strict_types=1);

/**
 * Poom Connect i18n
 *
 * Add new UI strings to lang/en.php and lang/th.php (and ja.php, zh.php, fil.php) using dot notation keys.
 * Use __('key') in PHP templates and set_flash(__('flash.key')) for messages.
 */

const SUPPORTED_LOCALES = ['en', 'th', 'ja', 'zh', 'fil'];
const DEFAULT_LOCALE = 'en';
const LOCALE_COOKIE = 'pc_locale';
const LOCALE_SESSION_KEY = 'locale';

/** @var array<string, string>|null */
$GLOBALS['_i18n_strings'] = null;

function supported_locales(): array
{
    return SUPPORTED_LOCALES;
}

function default_locale(): string
{
    return DEFAULT_LOCALE;
}

function current_locale(): string
{
    return $_SESSION[LOCALE_SESSION_KEY] ?? DEFAULT_LOCALE;
}

function init_locale(): void
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LOCALES, true)) {
        set_locale($_GET['lang']);
        return;
    }

    if (isset($_SESSION[LOCALE_SESSION_KEY]) && in_array($_SESSION[LOCALE_SESSION_KEY], SUPPORTED_LOCALES, true)) {
        return;
    }

    if (isset($_COOKIE[LOCALE_COOKIE]) && in_array($_COOKIE[LOCALE_COOKIE], SUPPORTED_LOCALES, true)) {
        $_SESSION[LOCALE_SESSION_KEY] = $_COOKIE[LOCALE_COOKIE];
        return;
    }

    $_SESSION[LOCALE_SESSION_KEY] = DEFAULT_LOCALE;
}

function set_locale(string $locale): void
{
    if (!in_array($locale, SUPPORTED_LOCALES, true)) {
        $locale = DEFAULT_LOCALE;
    }

    $_SESSION[LOCALE_SESSION_KEY] = $locale;
    setcookie(LOCALE_COOKIE, $locale, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function locale_switch_url(string $locale): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    $query['lang'] = $locale;

    return $path . '?' . http_build_query($query);
}

function load_translations(): array
{
    if ($GLOBALS['_i18n_strings'] !== null) {
        return $GLOBALS['_i18n_strings'];
    }

    $locale = current_locale();
    $file = APP_ROOT . '/lang/' . $locale . '.php';

    if (!is_file($file)) {
        $file = APP_ROOT . '/lang/' . DEFAULT_LOCALE . '.php';
    }

    $strings = require $file;
    $GLOBALS['_i18n_strings'] = flatten_translations($strings);

    return $GLOBALS['_i18n_strings'];
}

function flatten_translations(array $array, string $prefix = ''): array
{
    $flat = [];

    foreach ($array as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            $flat += flatten_translations($value, $fullKey);
        } else {
            $flat[$fullKey] = (string) $value;
        }
    }

    return $flat;
}

function __(string $key, array $replace = []): string
{
    $strings = load_translations();
    $text = $strings[$key] ?? null;

    if ($text === null && current_locale() !== DEFAULT_LOCALE) {
        $fallbackFile = APP_ROOT . '/lang/' . DEFAULT_LOCALE . '.php';
        if (is_file($fallbackFile)) {
            $fallback = flatten_translations(require $fallbackFile);
            $text = $fallback[$key] ?? $key;
        } else {
            $text = $key;
        }
    } elseif ($text === null) {
        $text = $key;
    }

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

function _e(string $key, array $replace = []): void
{
    echo e(__($key, $replace));
}

function app_name(): string
{
    return __('app.name');
}

function app_tagline(): string
{
    return __('app.tagline');
}

function status_label(string $status): string
{
    $key = 'status.' . strtolower($status);
    $translated = __($key);

    return $translated !== $key ? $translated : ucfirst($status);
}

function locale_label(?string $locale = null): string
{
    $locale = $locale ?? current_locale();

    return match ($locale) {
        'th' => __('nav.lang_th'),
        'ja' => __('nav.lang_ja'),
        'zh' => __('nav.lang_zh'),
        'fil' => __('nav.lang_fil'),
        'en' => __('nav.lang_en'),
        default => __('nav.lang_en'),
    };
}

function js_translations(): array
{
    return [
        'processing' => __('js.processing'),
        'approve_payment' => __('js.approve_payment'),
        'reject_payment' => __('js.reject_payment'),
        'end_event' => __('js.end_event'),
    ];
}
