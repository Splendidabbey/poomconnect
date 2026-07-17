<?php

declare(strict_types=1);

function ensure_templates_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NULL,
            slug VARCHAR(80) NOT NULL,
            name VARCHAR(120) NOT NULL,
            event_type VARCHAR(40) NOT NULL,
            description TEXT NULL,
            defaults JSON NOT NULL,
            is_system TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_template_slug (slug),
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    seed_system_event_templates();
    $ready = true;
}

function seed_system_event_templates(): void
{
    $templates = [
        [
            'speed-dating',
            'Speed Dating',
            'speed_dating',
            'Classic timed rotations for romantic connections.',
            [
                'round_duration' => 300,
                'round_count' => 8,
                'max_participants' => 40,
                'ticket_price' => 890,
                'dress_code' => 'Smart casual',
                'rules' => 'Be respectful. One conversation per round. No contact info sharing until mutual match.',
                'waitlist_enabled' => 1,
            ],
        ],
        [
            'networking',
            'Networking Mixer',
            'networking',
            'Professional networking with structured introductions.',
            [
                'round_duration' => 420,
                'round_count' => 6,
                'max_participants' => 80,
                'ticket_price' => 590,
                'dress_code' => 'Business casual',
                'rules' => 'Focus on meaningful connections. Exchange business cards after mutual interest.',
                'waitlist_enabled' => 1,
            ],
        ],
        [
            'corporate-mixer',
            'Corporate Mixer',
            'corporate',
            'Internal team building and cross-department connections.',
            [
                'round_duration' => 360,
                'round_count' => 5,
                'max_participants' => 100,
                'ticket_price' => 0,
                'dress_code' => 'Business formal',
                'rules' => 'Company policy applies. Professional conduct required.',
                'waitlist_enabled' => 0,
            ],
        ],
        [
            'university',
            'University Social',
            'university',
            'Campus social events for students and alumni.',
            [
                'round_duration' => 300,
                'round_count' => 6,
                'max_participants' => 60,
                'ticket_price' => 290,
                'dress_code' => 'Campus casual',
                'rules' => 'Student ID required. Be kind and inclusive.',
                'waitlist_enabled' => 1,
            ],
        ],
        [
            'lgbtq',
            'LGBTQ+ Mixer',
            'lgbtq',
            'Inclusive mixer in a welcoming, safe environment.',
            [
                'round_duration' => 300,
                'round_count' => 7,
                'max_participants' => 50,
                'ticket_price' => 490,
                'dress_code' => 'Come as you are',
                'rules' => 'Safe space. Zero tolerance for discrimination.',
                'waitlist_enabled' => 1,
            ],
        ],
        [
            'recruitment-fair',
            'Recruitment Fair',
            'recruitment',
            'Structured meet-and-greet for job seekers and employers.',
            [
                'round_duration' => 480,
                'round_count' => 4,
                'max_participants' => 120,
                'ticket_price' => 0,
                'dress_code' => 'Professional',
                'rules' => 'Bring resume. Structured rotation schedule.',
                'waitlist_enabled' => 1,
            ],
        ],
    ];

    $stmt = db()->prepare(
        'INSERT IGNORE INTO event_templates (slug, name, event_type, description, defaults, is_system, sort_order)
         VALUES (?, ?, ?, ?, ?, 1, ?)'
    );

    $order = 1;
    foreach ($templates as [$slug, $name, $type, $desc, $defaults]) {
        $stmt->execute([$slug, $name, $type, $desc, json_encode($defaults), $order++]);
    }
}

function get_event_templates(?int $orgId = null): array
{
    if ($orgId) {
        $stmt = db()->prepare(
            'SELECT * FROM event_templates WHERE is_system = 1 OR organization_id = ? ORDER BY sort_order ASC'
        );
        $stmt->execute([$orgId]);
    } else {
        $stmt = db()->query('SELECT * FROM event_templates WHERE is_system = 1 ORDER BY sort_order ASC');
    }

    return $stmt->fetchAll();
}

function get_event_template(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM event_templates WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function apply_template_to_event_data(array $template, array $overrides = []): array
{
    $defaults = json_decode($template['defaults'] ?? '{}', true) ?: [];

    return array_merge([
        'event_type' => $template['event_type'],
        'title' => $template['name'],
        'description' => $template['description'] ?? '',
    ], $defaults, $overrides);
}
