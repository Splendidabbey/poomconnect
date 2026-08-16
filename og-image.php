<?php

declare(strict_types=1);

define('SEO_SKIP_SESSION', true);

require_once __DIR__ . '/config/app.php';

$type = preg_replace('/[^a-z]/', '', (string) ($_GET['type'] ?? 'home')) ?: 'home';
$id = (int) ($_GET['id'] ?? 0);

seo_serve_og_image($type, $id);
