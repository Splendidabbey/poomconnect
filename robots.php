<?php

declare(strict_types=1);

define('SEO_SKIP_SESSION', true);

require_once __DIR__ . '/config/app.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Allow: /og-image\n";
echo "Disallow: /admin\n";
echo "Disallow: /organizer\n";
echo "Disallow: /api/\n";
echo "Disallow: /participant\n";
echo "Disallow: /config\n";
echo "Disallow: /includes\n";
echo "Disallow: /src\n";
echo "Disallow: /vendor\n";
echo "Disallow: /cron\n";
echo "Disallow: /migrate\n";
echo "Disallow: /login\n";
echo "Disallow: /pay\n";
echo "Disallow: /ticket\n";
echo "Disallow: /register\n";
echo "Disallow: /chat\n";
echo "Disallow: /notifications\n";
echo "Disallow: /my-events\n";
echo "Disallow: /profile\n";
echo "Disallow: /uploads/slips/\n";
echo "\n";
echo 'Sitemap: ' . base_url('sitemap.php') . "\n";
