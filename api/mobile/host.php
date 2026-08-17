<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$host = mobile_require_host();
$user = $host['user'];
$org = $host['org'];
$orgId = $org ? (int) $org['id'] : 0;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = array_merge($_POST, mobile_read_json_body());
$action = trim((string) ($_GET['action'] ?? $body['action'] ?? 'dashboard'));

function mobile_host_event_or_fail(int $eventId): array
{
    $event = mobile_get_event_for_host($eventId);
    if (!$event) {
        mobile_json_response(['success' => false, 'message' => 'Event not found'], 404);
    }

    return $event;
}

if ($action === 'dashboard' && $method === 'GET') {
    $stats = $orgId ? organizer_stats($orgId) : [
        'total_events' => 0,
        'total_participants' => 0,
        'pending_payments' => 0,
        'total_revenue' => 0,
        'matches_made' => 0,
    ];
    $live = 0;
    if ($orgId) {
        $liveStmt = db()->prepare("SELECT COUNT(*) FROM events WHERE organization_id = ? AND status = 'live'");
        $liveStmt->execute([$orgId]);
        $live = (int) $liveStmt->fetchColumn();
    }

    mobile_json_response([
        'success' => true,
        'dashboard' => [
            'total_events' => (int) $stats['total_events'],
            'total_participants' => (int) $stats['total_participants'],
            'pending_payments' => (int) $stats['pending_payments'],
            'total_revenue' => (float) $stats['total_revenue'],
            'currency' => current_currency(),
            'live_events' => $live,
        ],
    ]);
}

if ($action === 'events' && $method === 'GET') {
    $events = [];
    if ($orgId) {
        $stmt = db()->prepare(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
             FROM events e WHERE e.organization_id = ? ORDER BY e.event_date DESC, e.start_time DESC'
        );
        $stmt->execute([$orgId]);
        $events = $stmt->fetchAll();
    }

    mobile_json_response([
        'success' => true,
        'events' => array_map(
            static fn(array $event): array => mobile_event_payload($event, (int) $user['id']),
            $events
        ),
    ]);
}

if ($action === 'events' && $method === 'POST') {
    $title = trim((string) ($body['title'] ?? ''));
    $description = trim((string) ($body['description'] ?? ''));
    $venue = trim((string) ($body['venue'] ?? $body['location'] ?? ''));
    $city = trim((string) ($body['city'] ?? ''));
    $type = trim((string) ($body['type'] ?? $body['event_type'] ?? 'social'));
    if (!in_array($type, event_types(), true)) {
        $type = 'social';
    }
    $price = (float) ($body['ticket_price'] ?? 0);
    $starts = (string) ($body['starts_at'] ?? '');
    $ends = (string) ($body['ends_at'] ?? '');
    $startDate = $starts !== '' ? date('Y-m-d', strtotime($starts)) : date('Y-m-d', strtotime('+14 days'));
    $startTime = $starts !== '' ? date('H:i:s', strtotime($starts)) : '19:00:00';
    $endTime = $ends !== '' ? date('H:i:s', strtotime($ends)) : '23:00:00';

    if ($title === '' || $venue === '' || $city === '') {
        mobile_json_response(['success' => false, 'message' => 'Title, venue, and city are required'], 422);
    }

    $cover = null;
    if (isset($_FILES['cover_image'])) {
        $cover = save_upload($_FILES['cover_image'], 'events', 'cover');
    }

    $eventId = create_event_record($orgId, (int) $user['id'], [
        'title' => $title,
        'description' => $description,
        'location' => $venue,
        'city' => $city,
        'event_type' => $type,
        'category_id' => null,
        'event_date' => $startDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_participants' => 40,
        'ticket_price' => $price,
        'round_duration' => 300,
        'status' => 'published',
        'latitude' => null,
        'longitude' => null,
        'map_url' => '',
        'meta_title' => '',
        'meta_description' => '',
        'slug' => '',
    ], $cover, null);

    if (function_exists('ensure_live_state')) {
        ensure_live_state($eventId, 300);
    }

    $created = get_event_by_id($eventId);
    mobile_json_response([
        'success' => true,
        'event' => mobile_event_payload($created ?: ['id' => $eventId, 'title' => $title], (int) $user['id']),
    ], 201);
}

if ($action === 'participants' && $method === 'GET') {
    $eventId = (int) ($_GET['event_id'] ?? $body['event_id'] ?? 0);
    mobile_host_event_or_fail($eventId);
    $stmt = db()->prepare(
        'SELECT ep.*, u.full_name, u.avatar, t.checked_in, t.checked_in_at
         FROM event_participants ep
         JOIN users u ON u.id = ep.user_id
         LEFT JOIN tickets t ON t.event_id = ep.event_id AND t.user_id = ep.user_id
         WHERE ep.event_id = ?
         ORDER BY ep.created_at DESC'
    );
    $stmt->execute([$eventId]);

    mobile_json_response([
        'success' => true,
        'participants' => array_map(
            static function (array $row): array {
                $ticketStatus = 'pending';
                if (!empty($row['checked_in'])) {
                    $ticketStatus = 'used';
                } elseif (($row['ticket_status'] ?? '') === 'issued' || ($row['payment_status'] ?? '') === 'approved') {
                    $ticketStatus = 'valid';
                }

                return [
                    'id' => (int) $row['id'],
                    'event_id' => (int) $row['event_id'],
                    'member_id' => (int) $row['user_id'],
                    'full_name' => $row['full_name'],
                    'avatar_url' => !empty($row['avatar']) ? upload_url($row['avatar']) : default_avatar($row['full_name']),
                    'payment_status' => $row['payment_status'] ?? 'pending',
                    'ticket_status' => $ticketStatus,
                    'checked_in_at' => $row['checked_in_at'] ?? null,
                ];
            },
            $stmt->fetchAll()
        ),
    ]);
}

if ($action === 'payments' && $method === 'GET') {
    $rows = [];
    if ($orgId) {
        $stmt = db()->prepare(
            "SELECT p.*, u.full_name AS member_name, e.title AS event_title
             FROM payments p
             JOIN events e ON e.id = p.event_id
             JOIN users u ON u.id = p.user_id
             WHERE e.organization_id = ? AND p.payment_status IN ('pending')
             ORDER BY p.id DESC"
        );
        $stmt->execute([$orgId]);
        $rows = $stmt->fetchAll();
    }

    mobile_json_response([
        'success' => true,
        'payments' => array_map(
            static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'event_id' => (int) $row['event_id'],
                    'member_id' => (int) $row['user_id'],
                    'member_name' => $row['member_name'],
                    'amount' => (float) $row['amount'],
                    'currency' => current_currency(),
                    'method' => $row['payment_method'] ?? 'promptpay',
                    'status' => !empty($row['slip_image']) ? 'submitted' : 'pending',
                    'slip_image' => !empty($row['slip_image']) ? upload_url($row['slip_image']) : null,
                    'submitted_at' => $row['created_at'] ?? null,
                ];
            },
            $rows
        ),
    ]);
}

if ($action === 'payments' && $method === 'POST') {
    $paymentId = (int) ($body['payment_id'] ?? 0);
    $decision = trim((string) ($body['decision'] ?? ''));
    $payment = mobile_get_payment_for_host($paymentId);
    if (!$payment) {
        mobile_json_response(['success' => false, 'message' => 'Payment not found'], 404);
    }

    if ($decision === 'approve') {
        if (!approve_payment($paymentId, (int) $user['id'])) {
            mobile_json_response(['success' => false, 'message' => 'Could not approve payment'], 422);
        }
    } elseif ($decision === 'reject') {
        if (!reject_payment($paymentId, (int) $user['id'])) {
            mobile_json_response(['success' => false, 'message' => 'Could not reject payment'], 422);
        }
    } else {
        mobile_json_response(['success' => false, 'message' => 'Decision must be approve or reject'], 422);
    }

    mobile_json_response(['success' => true, 'message' => 'Payment updated']);
}

if ($action === 'checkin' && $method === 'POST') {
    $token = trim((string) ($body['qr_token'] ?? $body['ticket_code'] ?? ''));
    if ($token === '') {
        mobile_json_response(['success' => false, 'message' => 'Ticket code is empty.'], 422);
    }

    $result = checkin_ticket($token);
    if (empty($result['success'])) {
        mobile_json_response([
            'success' => false,
            'message' => strip_tags((string) ($result['message'] ?? 'Check-in failed')),
        ], !empty($result['warning']) ? 409 : 422);
    }

    $ticket = $result['ticket'] ?? [];
    mobile_json_response([
        'success' => true,
        'participant' => [
            'id' => (int) ($ticket['id'] ?? 0),
            'event_id' => (int) ($ticket['event_id'] ?? 0),
            'member_id' => (int) ($ticket['user_id'] ?? 0),
            'full_name' => $ticket['full_name'] ?? 'Guest',
            'payment_status' => 'approved',
            'ticket_status' => 'used',
            'checked_in_at' => date('Y-m-d H:i:s'),
        ],
        'message' => strip_tags((string) $result['message']),
    ]);
}

if ($action === 'live' && $method === 'POST') {
    $eventId = (int) ($body['event_id'] ?? 0);
    $liveAction = trim((string) ($body['live_action'] ?? ''));
    $event = mobile_host_event_or_fail($eventId);
    ensure_live_state($eventId, (int) $event['round_duration']);

    $liveState = get_live_state($eventId);
    $currentStatus = (string) ($liveState['event_status'] ?? '');

    if ($liveAction === 'start' && $currentStatus === 'paused') {
        resume_live_event($eventId);
    } elseif ($liveAction === 'start' && $currentStatus === 'live') {
        // Already running — do not regenerate round 1.
    } elseif ($liveAction === 'start') {
        $participants = get_checked_in_participants($eventId);
        if (count($participants) < 2) {
            mobile_json_response(['success' => false, 'message' => 'Need at least two checked-in guests'], 422);
        }
        $pairs = generate_ai_round_pairings($eventId, 1);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($pairs !== []) {
                save_round_pairings($eventId, $pairs);
            }
            $pdo->prepare(
                'UPDATE live_event_state SET current_round = 1, event_status = ?, timer_seconds = ?, timer_started_at = NOW(), updated_at = NOW() WHERE event_id = ?'
            )->execute(['live', (int) $event['round_duration'], $eventId]);
            $pdo->prepare("UPDATE events SET status = 'live' WHERE id = ?")->execute([$eventId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            mobile_json_response(['success' => false, 'message' => 'Could not start the night'], 500);
        }
    } elseif ($liveAction === 'next') {
        $liveState = get_live_state($eventId);
        $nextRound = (int) ($liveState['current_round'] ?? 0) + 1;
        $pairs = generate_ai_round_pairings($eventId, $nextRound);
        if ($pairs === []) {
            mobile_json_response(['success' => false, 'message' => 'No pairings for the next round'], 422);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            save_round_pairings($eventId, $pairs);
            $pdo->prepare(
                'UPDATE live_event_state SET current_round = ?, event_status = ?, timer_seconds = ?, timer_started_at = NOW(), updated_at = NOW() WHERE event_id = ?'
            )->execute([$nextRound, 'live', (int) $event['round_duration'], $eventId]);
            $pdo->prepare("UPDATE events SET status = 'live' WHERE id = ?")->execute([$eventId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            mobile_json_response(['success' => false, 'message' => 'Could not start the next round'], 500);
        }
    } elseif ($liveAction === 'pause') {
        db()->prepare('UPDATE live_event_state SET event_status = ?, updated_at = NOW() WHERE event_id = ?')
            ->execute(['paused', $eventId]);
        db()->prepare("UPDATE events SET status = 'paused' WHERE id = ?")->execute([$eventId]);
    } elseif ($liveAction === 'end') {
        db()->prepare('UPDATE live_event_state SET event_status = ?, updated_at = NOW() WHERE event_id = ?')
            ->execute(['ended', $eventId]);
        db()->prepare("UPDATE events SET status = 'completed' WHERE id = ?")->execute([$eventId]);
    } else {
        mobile_json_response(['success' => false, 'message' => 'Unknown live action'], 422);
    }

    mobile_json_response(['success' => true, 'message' => 'Live controls updated']);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
