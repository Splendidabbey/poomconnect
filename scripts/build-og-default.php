<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$_SESSION = [];

require_once APP_ROOT . '/includes/i18n.php';
init_locale();
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/seo.php';

$path = seo_write_default_og_image();
$size = is_file($path) ? filesize($path) : 0;

if ($size < 1000) {
    fwrite(STDERR, "Failed to write default OG image\n");
    exit(1);
}

echo "Wrote {$path} ({$size} bytes)\n";
