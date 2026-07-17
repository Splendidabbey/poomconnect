<?php

declare(strict_types=1);

const SUPPORTED_CURRENCIES = ['THB', 'USD', 'JPY', 'PHP', 'SGD'];
const DEFAULT_CURRENCY = 'THB';
const CURRENCY_COOKIE = 'pc_currency';
const CURRENCY_SESSION_KEY = 'currency';

/** Base currency for stored prices is THB. Demo display rates. */
const CURRENCY_RATES_FROM_THB = [
    'THB' => 1.0,
    'USD' => 0.028,
    'JPY' => 4.5,
    'PHP' => 1.58,
    'SGD' => 0.038,
];

function supported_currencies(): array
{
    return SUPPORTED_CURRENCIES;
}

function current_currency(): string
{
    $currency = $_SESSION[CURRENCY_SESSION_KEY] ?? DEFAULT_CURRENCY;

    return in_array($currency, SUPPORTED_CURRENCIES, true) ? $currency : DEFAULT_CURRENCY;
}

function init_currency(): void
{
    if (isset($_GET['currency']) && in_array($_GET['currency'], SUPPORTED_CURRENCIES, true)) {
        set_currency($_GET['currency']);
        return;
    }

    if (isset($_SESSION[CURRENCY_SESSION_KEY]) && in_array($_SESSION[CURRENCY_SESSION_KEY], SUPPORTED_CURRENCIES, true)) {
        return;
    }

    if (isset($_COOKIE[CURRENCY_COOKIE]) && in_array($_COOKIE[CURRENCY_COOKIE], SUPPORTED_CURRENCIES, true)) {
        $_SESSION[CURRENCY_SESSION_KEY] = $_COOKIE[CURRENCY_COOKIE];
        return;
    }

    $_SESSION[CURRENCY_SESSION_KEY] = default_currency_for_locale(current_locale());
}

function default_currency_for_locale(string $locale): string
{
    return match ($locale) {
        'th' => 'THB',
        'ja' => 'JPY',
        'zh' => 'USD',
        'fil' => 'PHP',
        default => 'USD',
    };
}

function set_currency(string $currency): void
{
    if (!in_array($currency, SUPPORTED_CURRENCIES, true)) {
        $currency = DEFAULT_CURRENCY;
    }

    $_SESSION[CURRENCY_SESSION_KEY] = $currency;
    setcookie(CURRENCY_COOKIE, $currency, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function currency_switch_url(string $currency): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    $query['currency'] = $currency;

    return $path . '?' . http_build_query($query);
}

function convert_from_thb(float $amountThb, ?string $currency = null): float
{
    $currency ??= current_currency();
    $rate = CURRENCY_RATES_FROM_THB[$currency] ?? 1.0;

    return round($amountThb * $rate, $currency === 'JPY' ? 0 : 2);
}

function currency_symbol(string $currency): string
{
    return match ($currency) {
        'THB' => '฿',
        'USD' => '$',
        'JPY' => '¥',
        'PHP' => '₱',
        'SGD' => 'S$',
        default => '',
    };
}

function format_currency(float $amountThb, ?string $currency = null): string
{
    $currency ??= current_currency();
    $converted = convert_from_thb($amountThb, $currency);
    $symbol = currency_symbol($currency);

    if ($currency === 'JPY') {
        return $symbol . number_format($converted, 0);
    }

    if ($currency === 'THB' && current_locale() === 'th') {
        return number_format($converted, 0) . ' บาท';
    }

    return $symbol . number_format($converted, 2) . ' ' . $currency;
}

function currency_label(string $currency): string
{
    $key = 'localization.currency_' . strtolower($currency);
    $label = __($key);

    return $label !== $key ? $label : $currency;
}

function ensure_localization_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    if (!table_has_column('users', 'preferred_currency')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN preferred_currency CHAR(3) NULL AFTER country");
        $pdo->exec("ALTER TABLE users ADD COLUMN preferred_locale VARCHAR(5) NULL AFTER preferred_currency");
    }

    if (!table_has_column('events', 'currency')) {
        $pdo->exec("ALTER TABLE events ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'THB' AFTER ticket_price");
    }

    $ready = true;
}

function locale_font_class(): string
{
    return match (current_locale()) {
        'th' => 'locale-th',
        'ja' => 'locale-ja',
        'zh' => 'locale-zh',
        'fil' => 'locale-fil',
        default => '',
    };
}

function locale_google_fonts(): array
{
    return match (current_locale()) {
        'th' => ['https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap'],
        'ja' => ['https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700;800&display=swap'],
        'zh' => ['https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;600;700;800&display=swap'],
        default => [],
    };
}
