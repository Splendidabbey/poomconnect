<?php

declare(strict_types=1);

const SEO_OG_WIDTH = 1200;
const SEO_OG_HEIGHT = 630;

function seo_og_locale(?string $locale = null): string
{
    return match ($locale ?? (function_exists('current_locale') ? current_locale() : 'en')) {
        'th' => 'th_TH',
        'ja' => 'ja_JP',
        'zh' => 'zh_CN',
        'fil' => 'fil_PH',
        default => 'en_US',
    };
}

function seo_hreflang(string $locale): string
{
    return match ($locale) {
        'th' => 'th-TH',
        'ja' => 'ja-JP',
        'zh' => 'zh-CN',
        'fil' => 'fil-PH',
        default => 'en',
    };
}

function seo_truncate(string $text, int $max = 160): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $max) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $max - 1), " \t\n\r.,;:-") . '…';
}

function seo_document_title(string $title, ?string $brand = null, ?string $tagline = null): string
{
    $brand = $brand ?? (function_exists('app_name') ? app_name() : 'Poom Connect');
    $tagline = $tagline ?? (function_exists('app_tagline') ? app_tagline() : '');
    $title = trim($title);

    if ($title === '' || strcasecmp($title, $brand) === 0) {
        return $tagline !== '' ? $brand . ' — ' . $tagline : $brand;
    }

    if (mb_stripos($title, $brand) !== false) {
        return $title;
    }

    return $title . ' | ' . $brand;
}

function seo_is_indexable(?string $scriptName = null): bool
{
    $script = str_replace('\\', '/', $scriptName ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/admin/', '/organizer/', '/api/', '/participant/', '/cron/'] as $dir) {
        if (str_contains($script, $dir)) {
            return false;
        }
    }

    $private = [
        'login.php', 'logout.php', 'pay.php', 'ticket.php', 'register.php',
        'chat.php', 'chat-thread.php', 'notifications.php', 'my-events.php',
        'loyalty.php', 'profile.php', 'migrate.php', 'seed.php', 'og-image.php',
        'sitemap.php', 'robots.php',
    ];

    return !in_array(basename($script), $private, true);
}

function seo_tracking_params(): array
{
    return ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'mc_cid', 'mc_eid', '_ga', 'ref'];
}

function seo_strip_tracking(string $url): string
{
    $parts = parse_url($url);
    if ($parts === false) {
        return $url;
    }

    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        foreach (seo_tracking_params() as $param) {
            unset($query[$param]);
        }
    }

    $rebuilt = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
    if (!empty($parts['port'])) {
        $rebuilt .= ':' . $parts['port'];
    }
    $rebuilt .= $parts['path'] ?? '/';
    if ($query !== []) {
        $rebuilt .= '?' . http_build_query($query);
    }

    return $rebuilt;
}

function seo_url_with_lang(string $url, string $locale): string
{
    $parts = parse_url($url) ?: [];
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    if ($locale === DEFAULT_LOCALE) {
        unset($query['lang']);
    } else {
        $query['lang'] = $locale;
    }

    $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
    if (!empty($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }
    $path = $parts['path'] ?? '/';
    $suffix = $query !== [] ? '?' . http_build_query($query) : '';

    return $origin . $path . $suffix;
}

function seo_canonical_url(?string $explicit = null): string
{
    if ($explicit) {
        return seo_strip_tracking($explicit);
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $query = $_GET;
    foreach (seo_tracking_params() as $param) {
        unset($query[$param]);
    }
    unset($query['lang']);

    $path = $script === 'index.php' ? '' : $script;
    if ($query !== []) {
        $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
    }

    return seo_strip_tracking(base_url($path));
}

function seo_hreflang_map(?string $canonical = null): array
{
    $canonical = seo_strip_tracking($canonical ?? seo_canonical_url());
    $map = [];
    foreach (SUPPORTED_LOCALES as $locale) {
        $map[seo_hreflang($locale)] = seo_url_with_lang($canonical, $locale);
    }
    $map['x-default'] = seo_url_with_lang($canonical, DEFAULT_LOCALE);

    return $map;
}

function seo_robots_content(?bool $indexable = null): string
{
    $indexable ??= seo_is_indexable();

    return $indexable ? 'index, follow' : 'noindex, nofollow';
}

function seo_default_image_path(): string
{
    $generated = APP_ROOT . '/poomconnect_images/og/default.jpg';
    if (is_file($generated)) {
        return $generated;
    }

    return APP_ROOT . '/poomconnect_images/og/og-backdrop.png';
}

function seo_share_image(array $context = []): string
{
    $type = $context['type'] ?? 'home';
    $id = (int) ($context['id'] ?? 0);
    $version = (string) ($context['v'] ?? '');

    if (in_array($type, ['home', 'page', 'signup'], true) && is_file(APP_ROOT . '/poomconnect_images/og/default.jpg')) {
        return brand_url('og/default.jpg');
    }

    $query = ['type' => $type];
    if ($id > 0) {
        $query['id'] = $id;
    }
    if ($version !== '') {
        $query['v'] = $version;
    }

    return base_url('og-image.php?' . http_build_query($query));
}

function seo_json_ld_organization(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => app_name(),
        'url' => base_url(),
        'logo' => brand_logo('lg'),
        'description' => __('app.meta_description'),
        'email' => 'hello@poomconnect.com',
        'slogan' => app_tagline(),
    ];
}

function seo_json_ld_website(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => app_name(),
        'url' => base_url(),
        'description' => __('app.meta_description'),
        'inLanguage' => array_map('seo_hreflang', SUPPORTED_LOCALES),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => base_url('events.php') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function seo_json_ld_event(array $event): array
{
    $start = trim((string) ($event['event_date'] ?? '')) . ' ' . trim((string) ($event['start_time'] ?? '18:00:00'));
    $end = trim((string) ($event['event_date'] ?? '')) . ' ' . trim((string) ($event['end_time'] ?? '22:00:00'));
    $status = match ($event['status'] ?? 'published') {
        'cancelled' => 'https://schema.org/EventCancelled',
        'completed' => 'https://schema.org/EventScheduled',
        'paused' => 'https://schema.org/EventPostponed',
        default => 'https://schema.org/EventScheduled',
    };

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $event['title'] ?? app_name(),
        'description' => seo_truncate((string) ($event['meta_description'] ?? $event['description'] ?? __('app.meta_description')), 300),
        'url' => event_url($event),
        'image' => seo_share_image([
            'type' => 'event',
            'id' => (int) ($event['id'] ?? 0),
            'v' => (string) ($event['updated_at'] ?? $event['id'] ?? ''),
        ]),
        'eventStatus' => $status,
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'organizer' => [
            '@type' => 'Organization',
            'name' => $event['organization_name'] ?? app_name(),
        ],
    ];

    $startTs = strtotime($start);
    if ($startTs) {
        $data['startDate'] = date('c', $startTs);
    }
    $endTs = strtotime($end);
    if ($endTs) {
        $data['endDate'] = date('c', $endTs);
    }

    $placeName = trim((string) ($event['location'] ?? ''));
    $city = trim((string) ($event['city'] ?? ''));
    if ($placeName !== '' || $city !== '') {
        $place = [
            '@type' => 'Place',
            'name' => $placeName !== '' ? $placeName : $city,
        ];
        $address = array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $city !== '' ? $city : null,
            'streetAddress' => $placeName !== '' ? $placeName : null,
            'addressCountry' => 'TH',
        ]);
        $place['address'] = $address;
        if (!empty($event['latitude']) && !empty($event['longitude'])) {
            $place['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $event['latitude'],
                'longitude' => (float) $event['longitude'],
            ];
        }
        $data['location'] = $place;
    }

    $price = (float) ($event['ticket_price'] ?? 0);
    $data['offers'] = [
        '@type' => 'Offer',
        'url' => event_url($event),
        'price' => number_format($price, 2, '.', ''),
        'priceCurrency' => 'THB',
        'availability' => get_spots_left($event) > 0
            ? 'https://schema.org/InStock'
            : 'https://schema.org/SoldOut',
        'validFrom' => date('c'),
    ];

    return $data;
}

function seo_json_ld_article(array $post): array
{
    $published = $post['published_at'] ?? $post['created_at'] ?? null;

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post['title'] ?? '',
        'description' => seo_truncate((string) ($post['meta_description'] ?? $post['excerpt'] ?? $post['content'] ?? ''), 200),
        'url' => blog_url($post),
        'image' => seo_share_image([
            'type' => 'article',
            'id' => (int) ($post['id'] ?? 0),
            'v' => (string) ($post['updated_at'] ?? $post['id'] ?? ''),
        ]),
        'datePublished' => $published ? date('c', strtotime((string) $published) ?: time()) : date('c'),
        'author' => [
            '@type' => 'Person',
            'name' => $post['author_name'] ?? app_name(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => app_name(),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => brand_logo('lg'),
            ],
        ],
        'mainEntityOfPage' => blog_url($post),
    ];
}

function seo_json_ld_breadcrumbs(array $items): array
{
    $elements = [];
    $position = 1;
    foreach ($items as $item) {
        $entry = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $item['name'],
        ];
        if (!empty($item['url'])) {
            $entry['item'] = $item['url'];
        }
        $elements[] = $entry;
        $position++;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];
}

function render_breadcrumbs(array $items): string
{
    if ($items === []) {
        return '';
    }

    ob_start();
    ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <ol class="breadcrumbs-list">
            <?php foreach ($items as $i => $item): ?>
                <li class="breadcrumbs-item">
                    <?php if (!empty($item['url']) && $i < count($items) - 1): ?>
                        <a href="<?= e($item['url']) ?>"><?= e($item['name']) ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= e($item['name']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php

    return (string) ob_get_clean();
}

function seo_render_head_json_ld(array $graphs): void
{
    foreach ($graphs as $graph) {
        if (!is_array($graph) || $graph === []) {
            continue;
        }
        $json = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        if ($json === false) {
            continue;
        }
        echo '<script type="application/ld+json">' . $json . "</script>\n";
    }
}

function seo_upload_abs(?string $relative): ?string
{
    if (!$relative) {
        return null;
    }
    $path = APP_ROOT . '/uploads/' . ltrim(str_replace('\\', '/', $relative), '/');

    return is_file($path) ? $path : null;
}

function seo_load_gd_image(string $path): ?GdImage
{
    if (!is_file($path)) {
        return null;
    }
    $info = @getimagesize($path);
    if ($info === false) {
        return null;
    }

    return match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
        IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
        IMAGETYPE_GIF => @imagecreatefromgif($path) ?: null,
        default => null,
    };
}

function seo_cover_crop(GdImage $src, int $width, int $height): GdImage
{
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    $srcRatio = $srcW / max(1, $srcH);
    $destRatio = $width / $height;

    if ($srcRatio > $destRatio) {
        $cropH = $srcH;
        $cropW = (int) round($srcH * $destRatio);
        $cropX = (int) max(0, ($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int) round($srcW / $destRatio);
        $cropX = 0;
        $cropY = (int) max(0, ($srcH - $cropH) / 3);
    }

    $dest = imagecreatetruecolor($width, $height);
    imagecopyresampled($dest, $src, 0, 0, $cropX, $cropY, $width, $height, $cropW, $cropH);

    return $dest;
}

function seo_key_black_to_transparent(GdImage $img): GdImage
{
    $w = imagesx($img);
    $h = imagesy($img);
    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefilledrectangle($out, 0, 0, $w, $h, $transparent);
    imagealphablending($out, true);

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($img, $x, $y);
            $a = ($rgba >> 24) & 0x7F;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if ($a < 120 && $r < 28 && $g < 28 && $b < 28) {
                continue;
            }
            $color = imagecolorallocatealpha($out, $r, $g, $b, $a);
            imagesetpixel($out, $x, $y, $color);
        }
    }

    return $out;
}

function seo_pick_font(): ?string
{
    $candidates = [
        APP_ROOT . '/assets/fonts/Inter-SemiBold.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/Library/Fonts/Arial Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf',
    ];
    foreach ($candidates as $font) {
        if (is_file($font)) {
            return $font;
        }
    }

    return null;
}

function seo_og_source_path(string $type, int $id): string
{
    if ($type === 'event' && $id > 0 && function_exists('get_event_by_id')) {
        $event = get_event_by_id($id);
        if ($event && in_array($event['status'] ?? '', ['published', 'live', 'completed'], true)) {
            $path = seo_upload_abs($event['og_image'] ?? null) ?? seo_upload_abs($event['cover_image'] ?? null);
            if ($path) {
                return $path;
            }
        }
    }

    if ($type === 'article' && $id > 0 && function_exists('get_blog_post_by_id')) {
        $post = get_blog_post_by_id($id);
        if ($post && ($post['status'] ?? '') === 'published') {
            $path = seo_upload_abs($post['cover_image'] ?? null);
            if ($path) {
                return $path;
            }
        }
    }

    if ($type === 'org' && $id > 0) {
        $stmt = db()->prepare('SELECT logo FROM organizations WHERE id = ? AND status = ? LIMIT 1');
        $stmt->execute([$id, 'active']);
        $logo = $stmt->fetchColumn();
        $path = $logo ? seo_upload_abs((string) $logo) : null;
        if ($path) {
            return $path;
        }
    }

    $backdropJpg = APP_ROOT . '/poomconnect_images/og/og-backdrop.jpg';
    $backdropPng = APP_ROOT . '/poomconnect_images/og/og-backdrop.png';

    return is_file($backdropJpg) ? $backdropJpg : (is_file($backdropPng) ? $backdropPng : seo_default_image_path());
}

function seo_og_overlay_title(string $type, int $id): string
{
    if ($type === 'event' && $id > 0) {
        $event = get_event_by_id($id);
        if ($event) {
            return (string) $event['title'];
        }
    }
    if ($type === 'article' && $id > 0) {
        $post = get_blog_post_by_id($id);
        if ($post) {
            return (string) $post['title'];
        }
    }
    if ($type === 'events') {
        return __('nav.events');
    }
    if ($type === 'blog') {
        return __('nav.blog');
    }

    return app_tagline();
}

function seo_compose_og_image(string $type, int $id = 0): GdImage
{
    $sourcePath = seo_og_source_path($type, $id);
    $src = seo_load_gd_image($sourcePath);
    if (!$src) {
        $canvas = imagecreatetruecolor(SEO_OG_WIDTH, SEO_OG_HEIGHT);
        $bg = imagecolorallocate($canvas, 5, 5, 16);
        imagefilledrectangle($canvas, 0, 0, SEO_OG_WIDTH, SEO_OG_HEIGHT, $bg);
    } else {
        $canvas = seo_cover_crop($src, SEO_OG_WIDTH, SEO_OG_HEIGHT);
        imagedestroy($src);
    }

    imagealphablending($canvas, true);

    for ($y = (int) (SEO_OG_HEIGHT * 0.45); $y < SEO_OG_HEIGHT; $y++) {
        $t = ($y - SEO_OG_HEIGHT * 0.45) / (SEO_OG_HEIGHT * 0.55);
        $alpha = (int) min(110, round($t * 110));
        $color = imagecolorallocatealpha($canvas, 5, 5, 16, 127 - $alpha);
        imageline($canvas, 0, $y, SEO_OG_WIDTH, $y, $color);
    }

    $logoPath = APP_ROOT . '/poomconnect_images/websites-logo/poom-logo-320x80.png';
    $logo = is_file($logoPath) ? seo_load_gd_image($logoPath) : null;
    if ($logo) {
        $logo = seo_key_black_to_transparent($logo);
        $logoW = (int) round(SEO_OG_WIDTH * 0.28);
        $logoH = (int) round($logoW * imagesy($logo) / max(1, imagesx($logo)));
        imagecopyresampled($canvas, $logo, 48, 40, 0, 0, $logoW, $logoH, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
    }

    $font = seo_pick_font();
    $title = seo_og_overlay_title($type, $id);
    if ($font && $title !== '') {
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 220, 214, 235);
        $size = 36;
        $maxWidth = SEO_OG_WIDTH - 96;
        $wrapped = seo_wrap_imagettf($title, $font, $size, $maxWidth);
        $lineHeight = 48;
        $textY = SEO_OG_HEIGHT - 70 - (count($wrapped) * $lineHeight);
        foreach ($wrapped as $line) {
            imagettftext($canvas, $size, 0, 48, $textY, $white, $font, $line);
            $textY += $lineHeight;
            $size = 28;
        }
        imagettftext($canvas, 16, 0, 48, SEO_OG_HEIGHT - 36, $muted, $font, app_name() . '  ·  ' . app_tagline());
    }

    return $canvas;
}

function seo_wrap_imagettf(string $text, string $font, int $size, int $maxWidth): array
{
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    $lines = [];
    $current = '';
    foreach ($words as $word) {
        $try = $current === '' ? $word : $current . ' ' . $word;
        $box = imagettfbbox($size, 0, $font, $try);
        $width = abs(($box[2] ?? 0) - ($box[0] ?? 0));
        if ($width > $maxWidth && $current !== '') {
            $lines[] = $current;
            $current = $word;
            if (count($lines) >= 2) {
                break;
            }
        } else {
            $current = $try;
        }
    }
    if ($current !== '' && count($lines) < 3) {
        $lines[] = $current;
    }

    return $lines === [] ? [$text] : $lines;
}

function seo_write_default_og_image(): string
{
    $dest = APP_ROOT . '/poomconnect_images/og/default.jpg';
    if (!function_exists('imagecreatetruecolor')) {
        return $dest;
    }

    $im = seo_compose_og_image('home', 0);
    imagejpeg($im, $dest, 86);
    imagedestroy($im);

    return $dest;
}

function seo_serve_og_image(string $type, int $id = 0): never
{
    $allowed = ['home', 'page', 'signup', 'events', 'blog', 'event', 'article', 'org'];
    if (!in_array($type, $allowed, true)) {
        $type = 'home';
        $id = 0;
    }

    $dir = APP_ROOT . '/uploads/og';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $source = seo_og_source_path($type, $id);
    $version = is_file($source) ? (string) filemtime($source) : '0';
    $cache = $dir . '/' . sha1($type . ':' . $id . ':' . $version) . '.jpg';

    if (!is_file($cache) || filesize($cache) < 1000) {
        if (!function_exists('imagecreatetruecolor')) {
            $fallback = seo_default_image_path();
            header('Content-Type: ' . (str_ends_with($fallback, '.png') ? 'image/png' : 'image/jpeg'));
            header('Cache-Control: public, max-age=86400');
            readfile($fallback);
            exit;
        }
        $im = seo_compose_og_image($type, $id);
        imagejpeg($im, $cache, 86);
        imagedestroy($im);
    }

    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . (string) filesize($cache));
    readfile($cache);
    exit;
}

function seo_sitemap_urls(): array
{
    $urls = [
        ['loc' => base_url(), 'changefreq' => 'daily', 'priority' => '1.0'],
        ['loc' => base_url('events.php'), 'changefreq' => 'hourly', 'priority' => '0.9'],
        ['loc' => base_url('blog.php'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => base_url('signup.php'), 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['loc' => base_url('privacy.php'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ['loc' => base_url('terms.php'), 'changefreq' => 'yearly', 'priority' => '0.3'],
    ];

    try {
        $events = db()->query(
            "SELECT slug, id, COALESCE(updated_at, created_at) AS lastmod
             FROM events
             WHERE status IN ('published', 'live', 'completed')
             ORDER BY event_date DESC
             LIMIT 1000"
        )->fetchAll();
        foreach ($events as $event) {
            $urls[] = [
                'loc' => event_url($event),
                'lastmod' => $event['lastmod'] ?? null,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $posts = db()->query(
            "SELECT slug, COALESCE(updated_at, published_at, created_at) AS lastmod
             FROM blog_posts
             WHERE status = 'published'
             ORDER BY published_at DESC
             LIMIT 500"
        )->fetchAll();
        foreach ($posts as $post) {
            $urls[] = [
                'loc' => blog_url($post),
                'lastmod' => $post['lastmod'] ?? null,
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        $orgs = db()->query(
            "SELECT slug FROM organizations WHERE status = 'active' AND slug IS NOT NULL AND slug != '' LIMIT 200"
        )->fetchAll();
        foreach ($orgs as $org) {
            $urls[] = [
                'loc' => base_url('org/index.php?org=' . urlencode((string) $org['slug'])),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }
    } catch (Throwable) {
        // Sitemap still returns static public URLs if the database is unavailable.
    }

    return $urls;
}
