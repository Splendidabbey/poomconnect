<?php

declare(strict_types=1);

/**
 * Poom Connect — Demo Seed Script
 * Run once after importing database.sql, then delete this file.
 */

require_once __DIR__ . '/config/app.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db();

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute(['admin@poomconnect.com']);

    if ($check->fetch()) {
        echo "Seed data already exists. Nothing to do.\n";
        exit;
    }

    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $organizerHash = password_hash('organizer123', PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $insertUser = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)'
    );

    $insertUser->execute(['Platform Admin', 'admin@poomconnect.com', $adminHash, '0800000001', 'admin']);
    $adminId = (int) $pdo->lastInsertId();

    $insertUser->execute(['Demo Organizer', 'organizer@poomconnect.com', $organizerHash, '0800000002', 'organizer']);
    $organizerId = (int) $pdo->lastInsertId();

    $insertOrg = $pdo->prepare(
        'INSERT INTO organizations (name, slug, primary_color, promptpay_number, bank_name, bank_account_name, bank_account_number, owner_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insertOrg->execute([
        'Poom Events Bangkok',
        'poom-events-bangkok',
        '#6C35FF',
        '0812345678',
        'Kasikorn Bank',
        'Poom Events Co., Ltd.',
        '123-4-56789-0',
        $organizerId,
    ]);
    $orgId = (int) $pdo->lastInsertId();

    $insertEvent = $pdo->prepare(
        'INSERT INTO events (organization_id, title, description, location, event_date, start_time, end_time, max_participants, ticket_price, round_duration, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $events = [
        [
            'Sunset Mixer Bangkok',
            'An evening of meaningful connections at a rooftop venue. Perfect for professionals looking to expand their network.',
            'The Roof @ Thonglor, Bangkok',
            date('Y-m-d', strtotime('+14 days')),
            '18:00:00',
            '22:00:00',
            40,
            990.00,
            300,
            'published',
        ],
        [
            'Speed Dating Night',
            'Fast-paced rounds of real conversations. Find your match in a fun, inclusive environment.',
            'EmQuartier, Bangkok',
            date('Y-m-d', strtotime('+21 days')),
            '19:00:00',
            '23:00:00',
            30,
            1290.00,
            240,
            'published',
        ],
        [
            'Startup Networking Social',
            'Connect with founders, investors, and innovators in the Thai startup ecosystem.',
            'Hubba Ekkamai, Bangkok',
            date('Y-m-d', strtotime('+30 days')),
            '17:30:00',
            '21:00:00',
            60,
            790.00,
            360,
            'published',
        ],
    ];

    foreach ($events as $ev) {
        $insertEvent->execute([
            $orgId,
            $ev[0],
            $ev[1],
            $ev[2],
            $ev[3],
            $ev[4],
            $ev[5],
            $ev[6],
            $ev[7],
            $ev[8],
            $ev[9],
            $organizerId,
        ]);

        $eventId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO live_event_state (event_id, current_round, event_status, timer_seconds) VALUES (?, 0, ?, ?)'
        )->execute([$eventId, 'waiting', $ev[8]]);
    }

    log_admin_action($adminId, 'seed_data', 'Demo seed data created');

    $pdo->commit();

    echo "Seed completed successfully!\n\n";
    echo "Admin login:\n  Email: admin@poomconnect.com\n  Password: admin123\n\n";
    echo "Organizer login:\n  Email: organizer@poomconnect.com\n  Password: organizer123\n\n";
    echo "IMPORTANT: Delete seed.php after setup.\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Seed failed: ' . $e->getMessage());
    http_response_code(500);
    echo "Seed failed. Check server logs.\n";
}
