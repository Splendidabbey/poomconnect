<?php

declare(strict_types=1);

function ensure_content_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL UNIQUE,
            type ENUM('event', 'blog', 'both') NOT NULL DEFAULT 'both',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            INDEX idx_event_images_event (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NULL,
            author_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NULL,
            title VARCHAR(220) NOT NULL,
            slug VARCHAR(240) NOT NULL UNIQUE,
            excerpt TEXT NULL,
            content LONGTEXT NOT NULL,
            cover_image VARCHAR(255) NULL,
            status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
            meta_title VARCHAR(200) NULL,
            meta_description VARCHAR(320) NULL,
            published_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_blog_status (status),
            INDEX idx_blog_published (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $eventColumns = [
        'slug' => "VARCHAR(220) NULL UNIQUE AFTER title",
        'category_id' => 'INT UNSIGNED NULL AFTER slug',
        'city' => "VARCHAR(100) NULL AFTER location",
        'event_type' => "ENUM('mixer','speed_dating','networking','workshop','social','other') NOT NULL DEFAULT 'social' AFTER city",
        'latitude' => 'DECIMAL(10,7) NULL AFTER event_type',
        'longitude' => 'DECIMAL(10,7) NULL AFTER latitude',
        'map_url' => 'VARCHAR(500) NULL AFTER longitude',
        'meta_title' => 'VARCHAR(200) NULL AFTER status',
        'meta_description' => 'VARCHAR(320) NULL AFTER meta_title',
        'og_image' => 'VARCHAR(255) NULL AFTER meta_description',
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    ];

    foreach ($eventColumns as $column => $definition) {
        if (!table_has_column('events', $column)) {
            $pdo->exec("ALTER TABLE events ADD COLUMN {$column} {$definition}");
        }
    }

    if (!table_has_index('events', 'idx_events_slug')) {
        try {
            $pdo->exec('CREATE INDEX idx_events_slug ON events (slug)');
        } catch (PDOException) {
            // slug may already be indexed via UNIQUE
        }
    }

    if (!table_has_index('events', 'idx_events_city')) {
        $pdo->exec('CREATE INDEX idx_events_city ON events (city)');
    }

    if (!table_has_index('events', 'idx_events_category')) {
        $pdo->exec('CREATE INDEX idx_events_category ON events (category_id)');
    }

    seed_default_categories();
    backfill_event_slugs();
    seed_demo_blog_if_empty();
    $ready = true;
}

function table_has_column(string $table, string $column): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function table_has_index(string $table, string $index): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);

    return (int) $stmt->fetchColumn() > 0;
}

function seed_default_categories(): void
{
    $defaults = [
        ['Mixers', 'mixers', 'event'],
        ['Speed Dating', 'speed-dating', 'event'],
        ['Networking', 'networking', 'event'],
        ['Workshops', 'workshops', 'event'],
        ['Social', 'social-events', 'event'],
        ['News', 'news', 'blog'],
        ['Tips & Guides', 'tips-guides', 'blog'],
        ['Stories', 'stories', 'blog'],
    ];

    $stmt = db()->prepare(
        'INSERT IGNORE INTO categories (name, slug, type) VALUES (?, ?, ?)'
    );

    foreach ($defaults as [$name, $slug, $type]) {
        $stmt->execute([$name, $slug, $type]);
    }
}

function backfill_event_slugs(): void
{
    $rows = db()->query("SELECT id, title FROM events WHERE slug IS NULL OR slug = ''")->fetchAll();
    foreach ($rows as $row) {
        $slug = unique_event_slug((string) $row['title'], (int) $row['id']);
        $update = db()->prepare('UPDATE events SET slug = ? WHERE id = ?');
        $update->execute([$slug, (int) $row['id']]);
    }
}

function seed_demo_blog_if_empty(): void
{
    $count = (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $admin = db()->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id ASC LIMIT 1")->fetch();
    $organizer = db()->query("SELECT u.id, o.id AS org_id FROM users u JOIN organizations o ON o.owner_id = u.id WHERE u.role = 'organizer' LIMIT 1")->fetch();
    if (!$admin) {
        return;
    }

    $tipsId = db()->query("SELECT id FROM categories WHERE slug = 'tips-guides' LIMIT 1")->fetchColumn();
    $storiesId = db()->query("SELECT id FROM categories WHERE slug = 'stories' LIMIT 1")->fetchColumn();

    $insert = db()->prepare(
        'INSERT INTO blog_posts (organization_id, author_id, category_id, title, slug, excerpt, content, status, meta_title, meta_description, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );

    $insert->execute([
        null,
        (int) $admin['id'],
        $tipsId ?: null,
        '5 Tips for Hosting Your First Mixer',
        '5-tips-hosting-first-mixer',
        'Make your first live matching event a success with these practical tips.',
        "Planning your first mixer? Start with a clear theme, keep rounds short, and create a welcoming check-in experience.\n\nFocus on balanced attendance and use QR ticketing to keep the flow smooth.",
        'published',
        '5 Tips for Hosting Your First Mixer',
        'Practical advice for organizers hosting their first Poom Connect mixer event.',
    ]);

    if ($organizer) {
        $insert->execute([
            (int) $organizer['org_id'],
            (int) $organizer['id'],
            $storiesId ?: null,
            'How We Filled 40 Seats in One Week',
            'filled-40-seats-one-week',
            'Our Sunset Mixer sold out fast — here is what worked.',
            "We promoted early on Instagram and LINE, offered an early-bird price, and highlighted the rooftop venue.\n\nReal photos and a clear event type helped attendees know exactly what to expect.",
            'published',
            'How We Filled 40 Seats in One Week',
            'A Bangkok organizer shares how they sold out their mixer in seven days.',
        ]);
    }
}

function event_types(): array
{
    return function_exists('platform_event_types') ? platform_event_types() : ['mixer', 'speed_dating', 'networking', 'workshop', 'social', 'other'];
}

function event_type_label(string $type): string
{
    $key = 'event_type.' . $type;
    $label = __($key);

    return $label !== $key ? $label : ucwords(str_replace('_', ' ', $type));
}

function unique_event_slug(string $title, ?int $excludeId = null): string
{
    $base = slugify($title) ?: 'event';
    $slug = $base;
    $i = 1;

    while (true) {
        $sql = 'SELECT id FROM events WHERE slug = ?';
        $params = [$slug];
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function unique_blog_slug(string $title, ?int $excludeId = null): string
{
    $base = slugify($title) ?: 'article';
    $slug = $base;
    $i = 1;

    while (true) {
        $sql = 'SELECT id FROM blog_posts WHERE slug = ?';
        $params = [$slug];
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function get_categories(string $type = 'both'): array
{
    if ($type === 'both') {
        $stmt = db()->query('SELECT * FROM categories ORDER BY name ASC');
    } else {
        $stmt = db()->prepare(
            "SELECT * FROM categories WHERE type IN (?, 'both') ORDER BY name ASC"
        );
        $stmt->execute([$type]);
    }

    return $stmt->fetchAll();
}

function get_category_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function event_url(array $event): string
{
    if (!empty($event['slug'])) {
        return base_url('event.php?slug=' . urlencode($event['slug']));
    }

    return base_url('event.php?id=' . (int) $event['id']);
}

function blog_url(array $post): string
{
    return base_url('article.php?slug=' . urlencode($post['slug']));
}

function get_event_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        'SELECT e.*, o.name AS organization_name, o.slug AS organization_slug, o.logo AS organization_logo,
                o.promptpay_number, o.bank_name, o.bank_account_name, o.bank_account_number,
                c.name AS category_name, c.slug AS category_slug,
                (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
         FROM events e
         JOIN organizations o ON o.id = e.organization_id
         LEFT JOIN categories c ON c.id = e.category_id
         WHERE e.slug = ? LIMIT 1'
    );
    $stmt->execute([$slug]);
    $event = $stmt->fetch();

    return $event ?: null;
}

function get_event_images(int $eventId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM event_images WHERE event_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$eventId]);

    return $stmt->fetchAll();
}

function save_event_gallery(int $eventId, array $files): void
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return;
    }

    $maxStmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM event_images WHERE event_id = ?');
    $maxStmt->execute([$eventId]);
    $order = (int) $maxStmt->fetchColumn();

    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
        $path = save_upload($file, 'events/gallery', 'gallery');
        if ($path) {
            $order++;
            $insert = db()->prepare(
                'INSERT INTO event_images (event_id, image_path, sort_order) VALUES (?, ?, ?)'
            );
            $insert->execute([$eventId, $path, $order]);
        }
    }
}

function delete_event_image(int $imageId, int $eventId): void
{
    $stmt = db()->prepare('DELETE FROM event_images WHERE id = ? AND event_id = ?');
    $stmt->execute([$imageId, $eventId]);
}

function search_events(array $filters = [], int $limit = 50): array
{
    $sql = "SELECT e.*, o.name AS organization_name, c.name AS category_name,
                   (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
            FROM events e
            JOIN organizations o ON o.id = e.organization_id
            LEFT JOIN categories c ON c.id = e.category_id
            WHERE e.status IN ('published', 'live')";
    $params = [];

    if (!empty($filters['q'])) {
        $sql .= ' AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ? OR e.city LIKE ?)';
        $q = '%' . $filters['q'] . '%';
        $params = array_merge($params, [$q, $q, $q, $q]);
    }

    if (!empty($filters['category_id'])) {
        $sql .= ' AND e.category_id = ?';
        $params[] = (int) $filters['category_id'];
    }

    if (!empty($filters['city'])) {
        $sql .= ' AND e.city LIKE ?';
        $params[] = '%' . $filters['city'] . '%';
    }

    if (!empty($filters['event_type'])) {
        $sql .= ' AND e.event_type = ?';
        $params[] = $filters['event_type'];
    }

    if (!empty($filters['date_from'])) {
        $sql .= ' AND e.event_date >= ?';
        $params[] = $filters['date_from'];
    } else {
        $sql .= ' AND e.event_date >= CURDATE()';
    }

    if (!empty($filters['date_to'])) {
        $sql .= ' AND e.event_date <= ?';
        $params[] = $filters['date_to'];
    }

    if (isset($filters['price_min']) && $filters['price_min'] !== '') {
        $sql .= ' AND e.ticket_price >= ?';
        $params[] = (float) $filters['price_min'];
    }

    if (isset($filters['price_max']) && $filters['price_max'] !== '') {
        $sql .= ' AND e.ticket_price <= ?';
        $params[] = (float) $filters['price_max'];
    }

    if (($filters['availability'] ?? '') === 'available') {
        $sql .= ' AND e.max_participants > (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id)';
    } elseif (($filters['availability'] ?? '') === 'sold_out') {
        $sql .= ' AND e.max_participants <= (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id)';
    }

    $sql .= ' ORDER BY e.event_date ASC, e.start_time ASC LIMIT ?';
    $params[] = $limit;

    $stmt = db()->prepare($sql);
    foreach ($params as $i => $value) {
        $stmt->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_event_cities(): array
{
    $stmt = db()->query(
        "SELECT DISTINCT city FROM events WHERE city IS NOT NULL AND city != '' AND status IN ('published','live') ORDER BY city ASC"
    );

    return array_column($stmt->fetchAll(), 'city');
}

function google_maps_embed_url(array $event): ?string
{
    if (!empty($event['latitude']) && !empty($event['longitude'])) {
        return 'https://maps.google.com/maps?q=' . urlencode($event['latitude'] . ',' . $event['longitude']) . '&z=15&output=embed';
    }

    if (!empty($event['map_url'])) {
        return $event['map_url'];
    }

    $query = trim(($event['location'] ?? '') . ' ' . ($event['city'] ?? ''));
    if ($query === '') {
        return null;
    }

    return 'https://maps.google.com/maps?q=' . urlencode($query) . '&z=15&output=embed';
}

function page_meta(array $overrides = []): array
{
    return array_merge([
        'title' => app_name(),
        'description' => __('app.meta_description'),
        'image' => brand_logo('lg'),
        'url' => base_url(),
        'type' => 'website',
    ], $overrides);
}

function event_page_meta(array $event): array
{
    $image = $event['og_image'] ?? $event['cover_image'] ?? null;

    return page_meta([
        'title' => $event['meta_title'] ?: $event['title'],
        'description' => $event['meta_description'] ?: mb_substr(strip_tags((string) ($event['description'] ?? '')), 0, 160),
        'image' => $image ? upload_url($image) : default_event_image(),
        'url' => event_url($event),
        'type' => 'event',
    ]);
}

function blog_page_meta(array $post): array
{
    $image = $post['cover_image'] ?? null;

    return page_meta([
        'title' => $post['meta_title'] ?: $post['title'],
        'description' => $post['meta_description'] ?: mb_substr(strip_tags((string) ($post['excerpt'] ?? $post['content'] ?? '')), 0, 160),
        'image' => $image ? upload_url($image) : brand_logo('lg'),
        'url' => blog_url($post),
        'type' => 'article',
    ]);
}

function search_blog_posts(array $filters = [], int $limit = 20): array
{
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   u.full_name AS author_name, o.name AS organization_name
            FROM blog_posts p
            LEFT JOIN categories c ON c.id = p.category_id
            JOIN users u ON u.id = p.author_id
            LEFT JOIN organizations o ON o.id = p.organization_id
            WHERE p.status = 'published'";
    $params = [];

    if (!empty($filters['q'])) {
        $sql .= ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
        $q = '%' . $filters['q'] . '%';
        $params = array_merge($params, [$q, $q, $q]);
    }

    if (!empty($filters['category_id'])) {
        $sql .= ' AND p.category_id = ?';
        $params[] = (int) $filters['category_id'];
    }

    $sql .= ' ORDER BY COALESCE(p.published_at, p.created_at) DESC LIMIT ?';
    $params[] = $limit;

    $stmt = db()->prepare($sql);
    foreach ($params as $i => $value) {
        $stmt->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_blog_post_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                u.full_name AS author_name, o.name AS organization_name
         FROM blog_posts p
         LEFT JOIN categories c ON c.id = p.category_id
         JOIN users u ON u.id = p.author_id
         LEFT JOIN organizations o ON o.id = p.organization_id
         WHERE p.slug = ? AND p.status = 'published' LIMIT 1"
    );
    $stmt->execute([$slug]);

    return $stmt->fetch() ?: null;
}

function get_blog_post_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM blog_posts WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function user_can_manage_blog_post(array $post): bool
{
    if (in_array(current_user_role(), ['admin', 'super_admin'], true)) {
        return true;
    }

    $user = current_user();
    if (!$user) {
        return false;
    }

    if ((int) $post['author_id'] === (int) $user['id']) {
        return true;
    }

    $org = get_organization_for_user((int) $user['id']);

    return $org && (int) ($post['organization_id'] ?? 0) === (int) $org['id'];
}

function parse_event_form_data(array $post): array
{
    return [
        'title' => trim($post['title'] ?? ''),
        'description' => trim($post['description'] ?? ''),
        'location' => trim($post['location'] ?? ''),
        'city' => trim($post['city'] ?? ''),
        'event_type' => in_array($post['event_type'] ?? '', event_types(), true) ? $post['event_type'] : 'social',
        'category_id' => !empty($post['category_id']) ? (int) $post['category_id'] : null,
        'event_date' => $post['event_date'] ?? '',
        'start_time' => $post['start_time'] ?? '',
        'end_time' => $post['end_time'] ?? '',
        'max_participants' => max(1, (int) ($post['max_participants'] ?? 50)),
        'ticket_price' => (float) ($post['ticket_price'] ?? 0),
        'round_duration' => max(60, (int) ($post['round_duration'] ?? 300)),
        'status' => $post['status'] ?? 'draft',
        'latitude' => ($post['latitude'] ?? '') !== '' ? (float) $post['latitude'] : null,
        'longitude' => ($post['longitude'] ?? '') !== '' ? (float) $post['longitude'] : null,
        'map_url' => trim($post['map_url'] ?? ''),
        'meta_title' => trim($post['meta_title'] ?? ''),
        'meta_description' => trim($post['meta_description'] ?? ''),
        'slug' => trim($post['slug'] ?? ''),
    ];
}

function create_event_record(int $orgId, int $userId, array $data, ?string $coverPath, ?string $ogImagePath): int
{
    $slug = $data['slug'] !== '' ? slugify($data['slug']) : unique_event_slug($data['title']);

    $stmt = db()->prepare(
        'INSERT INTO events (
            organization_id, title, slug, category_id, description, cover_image, location, city, event_type,
            latitude, longitude, map_url, event_date, start_time, end_time, max_participants, ticket_price,
            round_duration, status, meta_title, meta_description, og_image, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $orgId,
        $data['title'],
        $slug,
        $data['category_id'],
        $data['description'],
        $coverPath,
        $data['location'],
        $data['city'],
        $data['event_type'],
        $data['latitude'],
        $data['longitude'],
        $data['map_url'] ?: null,
        $data['event_date'],
        $data['start_time'],
        $data['end_time'],
        $data['max_participants'],
        $data['ticket_price'],
        $data['round_duration'],
        in_array($data['status'], ['draft', 'published'], true) ? $data['status'] : 'draft',
        $data['meta_title'] ?: null,
        $data['meta_description'] ?: null,
        $ogImagePath,
        $userId,
    ]);

    return (int) db()->lastInsertId();
}

function update_event_record(int $eventId, array $data, ?string $coverPath, ?string $ogImagePath): void
{
    $slug = $data['slug'] !== '' ? slugify($data['slug']) : unique_event_slug($data['title'], $eventId);
    $allowedStatuses = ['draft', 'published', 'live', 'paused', 'completed', 'cancelled'];
    $status = in_array($data['status'], $allowedStatuses, true) ? $data['status'] : 'draft';

    $stmt = db()->prepare(
        'UPDATE events SET title=?, slug=?, category_id=?, description=?, cover_image=?, location=?, city=?, event_type=?,
            latitude=?, longitude=?, map_url=?, event_date=?, start_time=?, end_time=?, max_participants=?, ticket_price=?,
            round_duration=?, status=?, meta_title=?, meta_description=?, og_image=? WHERE id=?'
    );
    $stmt->execute([
        $data['title'],
        $slug,
        $data['category_id'],
        $data['description'],
        $coverPath,
        $data['location'],
        $data['city'],
        $data['event_type'],
        $data['latitude'],
        $data['longitude'],
        $data['map_url'] ?: null,
        $data['event_date'],
        $data['start_time'],
        $data['end_time'],
        $data['max_participants'],
        $data['ticket_price'],
        $data['round_duration'],
        $status,
        $data['meta_title'] ?: null,
        $data['meta_description'] ?: null,
        $ogImagePath,
        $eventId,
    ]);
}

function parse_blog_form_data(array $post): array
{
    return [
        'title' => trim($post['title'] ?? ''),
        'slug' => trim($post['slug'] ?? ''),
        'excerpt' => trim($post['excerpt'] ?? ''),
        'content' => trim($post['content'] ?? ''),
        'category_id' => !empty($post['category_id']) ? (int) $post['category_id'] : null,
        'status' => in_array($post['status'] ?? '', ['draft', 'published'], true) ? $post['status'] : 'draft',
        'meta_title' => trim($post['meta_title'] ?? ''),
        'meta_description' => trim($post['meta_description'] ?? ''),
    ];
}

function save_blog_post(?int $postId, int $authorId, ?int $orgId, array $data, ?string $coverPath): int
{
    $slug = $data['slug'] !== '' ? slugify($data['slug']) : unique_blog_slug($data['title'], $postId);
    $publishedAt = $data['status'] === 'published' ? date('Y-m-d H:i:s') : null;

    if ($postId) {
        $existing = get_blog_post_by_id($postId);
        if ($existing && $existing['status'] === 'published' && $data['status'] === 'published') {
            $publishedAt = $existing['published_at'] ?? $publishedAt;
        }

        $stmt = db()->prepare(
            'UPDATE blog_posts SET organization_id=?, category_id=?, title=?, slug=?, excerpt=?, content=?, cover_image=?,
                status=?, meta_title=?, meta_description=?, published_at=? WHERE id=?'
        );
        $stmt->execute([
            $orgId,
            $data['category_id'],
            $data['title'],
            $slug,
            $data['excerpt'] ?: null,
            $data['content'],
            $coverPath,
            $data['status'],
            $data['meta_title'] ?: null,
            $data['meta_description'] ?: null,
            $publishedAt,
            $postId,
        ]);

        return $postId;
    }

    $stmt = db()->prepare(
        'INSERT INTO blog_posts (organization_id, author_id, category_id, title, slug, excerpt, content, cover_image, status, meta_title, meta_description, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $orgId,
        $authorId,
        $data['category_id'],
        $data['title'],
        $slug,
        $data['excerpt'] ?: null,
        $data['content'],
        $coverPath,
        $data['status'],
        $data['meta_title'] ?: null,
        $data['meta_description'] ?: null,
        $publishedAt,
    ]);

    return (int) db()->lastInsertId();
}

function get_blog_posts_for_admin(): array
{
    return db()->query(
        'SELECT p.*, c.name AS category_name, u.full_name AS author_name, o.name AS organization_name
         FROM blog_posts p
         LEFT JOIN categories c ON c.id = p.category_id
         JOIN users u ON u.id = p.author_id
         LEFT JOIN organizations o ON o.id = p.organization_id
         ORDER BY COALESCE(p.published_at, p.created_at) DESC'
    )->fetchAll();
}

function get_blog_posts_for_org(int $orgId): array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name
         FROM blog_posts p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.organization_id = ?
         ORDER BY COALESCE(p.published_at, p.created_at) DESC'
    );
    $stmt->execute([$orgId]);

    return $stmt->fetchAll();
}
