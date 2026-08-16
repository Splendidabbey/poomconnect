<?php

declare(strict_types=1);

define('SEO_SKIP_SESSION', true);

require_once __DIR__ . '/config/app.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$urls = seo_sitemap_urls();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($urls as $entry): ?>
  <url>
    <loc><?= e($entry['loc']) ?></loc>
<?php if (!empty($entry['lastmod'])): ?>
    <lastmod><?= e(date('Y-m-d', strtotime((string) $entry['lastmod']) ?: time())) ?></lastmod>
<?php endif; ?>
    <changefreq><?= e($entry['changefreq'] ?? 'weekly') ?></changefreq>
    <priority><?= e($entry['priority'] ?? '0.5') ?></priority>
<?php foreach (seo_hreflang_map($entry['loc']) as $lang => $href): ?>
    <xhtml:link rel="alternate" hreflang="<?= e($lang) ?>" href="<?= e($href) ?>"/>
<?php endforeach; ?>
  </url>
<?php endforeach; ?>
</urlset>
