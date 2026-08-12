<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

if (is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

// Fixed test key so Crypto tests are deterministic; never used outside the test suite.
$_ENV['APP_ENCRYPTION_KEY'] = base64_encode(str_repeat('t', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

require_once APP_ROOT . '/includes/i18n.php';
require_once APP_ROOT . '/includes/localization.php';
