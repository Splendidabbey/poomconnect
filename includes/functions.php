<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function render_flash(): string
{
    $flash = get_flash();

    if (!$flash) {
        return '';
    }

    $type = e($flash['type']);
    $message = e($flash['message']);

    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

function format_date(?string $date): string
{
    if (!$date) {
        return '';
    }

    $ts = strtotime($date);

    if (current_locale() === 'th') {
        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];
        $month = (int) date('n', $ts);

        return date('j', $ts) . ' ' . ($months[$month] ?? '') . ' ' . ((int) date('Y', $ts) + 543);
    }

    return date('M j, Y', $ts);
}

function format_time(?string $time): string
{
    if (!$time) {
        return '';
    }

    $ts = strtotime($time);

    return current_locale() === 'th'
        ? date('H:i', $ts) . ' น.'
        : date('g:i A', $ts);
}

function spots_left_label(int $count): string
{
    return $count > 0
        ? __('common.spots_left', ['count' => $count])
        : __('common.sold_out');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function generate_qr_token(): string
{
    return bin2hex(random_bytes(16));
}

function log_admin_action(int $userId, string $action, ?string $details = null): void
{
    $stmt = db()->prepare('INSERT INTO admin_logs (user_id, action, details) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $action, $details]);
}

function get_organization_for_user(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM organizations WHERE owner_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $org = $stmt->fetch();

    return $org ?: null;
}

function get_event_by_id(int $eventId): ?array
{
    $stmt = db()->prepare(
        'SELECT e.*, o.name AS organization_name, o.slug AS organization_slug, o.logo AS organization_logo,
                o.promptpay_number, o.bank_name, o.bank_account_name, o.bank_account_number,
                c.name AS category_name, c.slug AS category_slug,
                (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
         FROM events e
         JOIN organizations o ON o.id = e.organization_id
         LEFT JOIN categories c ON c.id = e.category_id
         WHERE e.id = ? LIMIT 1'
    );
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    return $event ?: null;
}

function get_published_events(int $limit = 12): array
{
    $stmt = db()->prepare(
        "SELECT e.*, o.name AS organization_name,
                (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
         FROM events e
         JOIN organizations o ON o.id = e.organization_id
         WHERE e.status IN ('published', 'live')
           AND e.event_date >= CURDATE()
         ORDER BY e.event_date ASC, e.start_time ASC
         LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_spots_left(array $event): int
{
    $count = (int) ($event['participant_count'] ?? 0);
    return max(0, (int) $event['max_participants'] - $count);
}

function validate_upload(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => __('validation.invalid_upload')];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => __('validation.select_file')];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => __('validation.upload_failed')];
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'error' => __('validation.file_too_large')];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        return ['ok' => false, 'error' => __('validation.invalid_image_type')];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS, true)) {
        return ['ok' => false, 'error' => __('validation.invalid_extension')];
    }

    return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
}

function save_upload(array $file, string $directory, string $prefix = 'file'): ?string
{
    $validation = validate_upload($file);

    if (!$validation['ok']) {
        return null;
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $validation['ext'];
    $targetDir = APP_ROOT . '/uploads/' . trim($directory, '/');
    $targetPath = $targetDir . '/' . $filename;

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return null;
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return trim($directory, '/') . '/' . $filename;
}

function create_or_get_participant_user(string $fullName, string $email, ?string $phone, ?string $lineId): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        return $existing;
    }

    $passwordHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

    $insert = db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, phone, line_id, role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([$fullName, $email, $passwordHash, $phone, $lineId, 'participant']);

    $stmt->execute([$email]);
    return $stmt->fetch();
}

function register_participant_for_event(int $eventId, int $userId, float $amount): bool
{
    $pdo = db();

    $check = $pdo->prepare('SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?');
    $check->execute([$eventId, $userId]);

    if ($check->fetch()) {
        return false;
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO event_participants (event_id, user_id, payment_status, ticket_status)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$eventId, $userId, 'pending', 'none']);

        $payment = $pdo->prepare(
            'INSERT INTO payments (event_id, user_id, amount, payment_method, payment_status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $payment->execute([$eventId, $userId, $amount, 'promptpay', 'pending']);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Registration failed: ' . $e->getMessage());
        return false;
    }
}

function approve_payment(int $paymentId, int $approvedBy): bool
{
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment || $payment['payment_status'] !== 'pending') {
        return false;
    }

    $pdo->beginTransaction();

    try {
        $update = $pdo->prepare(
            'UPDATE payments SET payment_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?'
        );
        $update->execute(['approved', $approvedBy, $paymentId]);

        $participant = $pdo->prepare(
            'UPDATE event_participants SET payment_status = ? WHERE event_id = ? AND user_id = ?'
        );
        $participant->execute(['approved', $payment['event_id'], $payment['user_id']]);

        generate_ticket((int) $payment['event_id'], (int) $payment['user_id']);
        notify_payment_approved((int) $payment['user_id'], (int) $payment['event_id']);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Payment approval failed: ' . $e->getMessage());
        return false;
    }
}

function reject_payment(int $paymentId, int $approvedBy): bool
{
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment || $payment['payment_status'] !== 'pending') {
        return false;
    }

    $pdo->beginTransaction();

    try {
        $update = $pdo->prepare(
            'UPDATE payments SET payment_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?'
        );
        $update->execute(['rejected', $approvedBy, $paymentId]);

        $participant = $pdo->prepare(
            'UPDATE event_participants SET payment_status = ? WHERE event_id = ? AND user_id = ?'
        );
        $participant->execute(['rejected', $payment['event_id'], $payment['user_id']]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Payment rejection failed: ' . $e->getMessage());
        return false;
    }
}

function generate_ticket(int $eventId, int $userId): ?string
{
    $pdo = db();

    $check = $pdo->prepare('SELECT qr_token FROM tickets WHERE event_id = ? AND user_id = ? LIMIT 1');
    $check->execute([$eventId, $userId]);

    $existing = $check->fetch();

    if ($existing) {
        return $existing['qr_token'];
    }

    $token = generate_qr_token();

    $stmt = $pdo->prepare(
        'INSERT INTO tickets (event_id, user_id, qr_token) VALUES (?, ?, ?)'
    );
    $stmt->execute([$eventId, $userId, $token]);

    $update = $pdo->prepare(
        'UPDATE event_participants SET ticket_status = ? WHERE event_id = ? AND user_id = ?'
    );
    $update->execute(['issued', $eventId, $userId]);

    return $token;
}

function checkin_ticket(string $qrToken): array
{
    $stmt = db()->prepare(
        'SELECT t.*, u.full_name, e.title AS event_title
         FROM tickets t
         JOIN users u ON u.id = t.user_id
         JOIN events e ON e.id = t.event_id
         WHERE t.qr_token = ? LIMIT 1'
    );
    $stmt->execute([$qrToken]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        return ['success' => false, 'message' => __('checkin.invalid_qr')];
    }

    if ((int) $ticket['checked_in'] === 1) {
        return [
            'success' => false,
            'warning' => true,
            'message' => __('checkin.already_checked_in', ['name' => $ticket['full_name']]),
            'ticket' => $ticket,
        ];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $updateTicket = $pdo->prepare(
            'UPDATE tickets SET checked_in = 1, checked_in_at = NOW() WHERE id = ?'
        );
        $updateTicket->execute([$ticket['id']]);

        $updateParticipant = $pdo->prepare(
            'UPDATE event_participants SET checked_in = 1 WHERE event_id = ? AND user_id = ?'
        );
        $updateParticipant->execute([$ticket['event_id'], $ticket['user_id']]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => __('checkin.success', ['name' => $ticket['full_name']]),
            'ticket' => $ticket,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Check-in failed: ' . $e->getMessage());
        return ['success' => false, 'message' => __('checkin.failed')];
    }
}

function get_live_state(int $eventId): ?array
{
    $stmt = db()->prepare('SELECT * FROM live_event_state WHERE event_id = ? LIMIT 1');
    $stmt->execute([$eventId]);
    $state = $stmt->fetch();

    return $state ?: null;
}

function ensure_live_state(int $eventId, int $roundDuration): void
{
    $stmt = db()->prepare('SELECT id FROM live_event_state WHERE event_id = ? LIMIT 1');
    $stmt->execute([$eventId]);

    if ($stmt->fetch()) {
        return;
    }

    $insert = db()->prepare(
        'INSERT INTO live_event_state (event_id, current_round, event_status, timer_seconds)
         VALUES (?, 0, ?, ?)'
    );
    $insert->execute([$eventId, 'waiting', $roundDuration]);
}

function get_checked_in_participants(int $eventId): array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.full_name, u.avatar
         FROM event_participants ep
         JOIN users u ON u.id = ep.user_id
         WHERE ep.event_id = ?
           AND ep.payment_status = 'approved'
           AND ep.checked_in = 1
         ORDER BY u.full_name ASC"
    );
    $stmt->execute([$eventId]);

    return $stmt->fetchAll();
}

function get_previous_pairings(int $eventId): array
{
    $stmt = db()->prepare(
        'SELECT participant_a, participant_b FROM rounds WHERE event_id = ?'
    );
    $stmt->execute([$eventId]);

    $pairs = [];

    foreach ($stmt->fetchAll() as $row) {
        $a = (int) $row['participant_a'];
        $b = (int) $row['participant_b'];
        $key = min($a, $b) . '-' . max($a, $b);
        $pairs[$key] = true;
    }

    return $pairs;
}

function generate_round_pairings(int $eventId, int $roundNumber): array
{
    $participants = get_checked_in_participants($eventId);
    $count = count($participants);

    if ($count < 2) {
        return [];
    }

    $previous = get_previous_pairings($eventId);
    $ids = array_column($participants, 'id');
    shuffle($ids);

    $pairs = [];
    $used = [];
    $tableNumber = 1;

    for ($i = 0; $i < $count; $i++) {
        $a = (int) $ids[$i];

        if (isset($used[$a])) {
            continue;
        }

        $partner = null;

        for ($j = $i + 1; $j < $count; $j++) {
            $b = (int) $ids[$j];

            if (isset($used[$b])) {
                continue;
            }

            $key = min($a, $b) . '-' . max($a, $b);

            if (!isset($previous[$key])) {
                $partner = $b;
                break;
            }
        }

        if ($partner === null) {
            for ($j = $i + 1; $j < $count; $j++) {
                $b = (int) $ids[$j];
                if (!isset($used[$b])) {
                    $partner = $b;
                    break;
                }
            }
        }

        if ($partner === null) {
            continue;
        }

        $pairs[] = [
            'round_number' => $roundNumber,
            'table_number' => $tableNumber++,
            'participant_a' => $a,
            'participant_b' => $partner,
        ];

        $used[$a] = true;
        $used[$partner] = true;
    }

    return $pairs;
}

function save_round_pairings(int $eventId, array $pairs): void
{
    $stmt = db()->prepare(
        'INSERT INTO rounds (event_id, round_number, table_number, participant_a, participant_b, started_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );

    foreach ($pairs as $pair) {
        $stmt->execute([
            $eventId,
            $pair['round_number'],
            $pair['table_number'],
            $pair['participant_a'],
            $pair['participant_b'],
        ]);
    }
}

function process_match_votes(int $eventId, int $roundId, int $voterId, int $targetId, string $vote): void
{
    if (!in_array($vote, ['like', 'friend', 'business', 'pass'], true)) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO match_votes (event_id, round_id, voter_id, target_id, vote)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE vote = VALUES(vote)'
    );
    $stmt->execute([$eventId, $roundId, $voterId, $targetId, $vote]);

    if (!in_array($vote, ['like', 'friend', 'business'], true)) {
        return;
    }

    $check = db()->prepare(
        'SELECT vote FROM match_votes WHERE event_id = ? AND voter_id = ? AND target_id = ? LIMIT 1'
    );
    $check->execute([$eventId, $targetId, $voterId]);
    $mutual = $check->fetch();

    if ($mutual && $mutual['vote'] === $vote) {
        record_mutual_match($eventId, $voterId, $targetId, $vote);
    }
}

function organizer_stats(int $organizationId): array
{
    $pdo = db();

    $events = $pdo->prepare('SELECT COUNT(*) FROM events WHERE organization_id = ?');
    $events->execute([$organizationId]);
    $totalEvents = (int) $events->fetchColumn();

    $participants = $pdo->prepare(
        'SELECT COUNT(*) FROM event_participants ep
         JOIN events e ON e.id = ep.event_id
         WHERE e.organization_id = ?'
    );
    $participants->execute([$organizationId]);
    $totalParticipants = (int) $participants->fetchColumn();

    $pending = $pdo->prepare(
        "SELECT COUNT(*) FROM payments p
         JOIN events e ON e.id = p.event_id
         WHERE e.organization_id = ? AND p.payment_status = 'pending'"
    );
    $pending->execute([$organizationId]);
    $pendingPayments = (int) $pending->fetchColumn();

    $revenue = $pdo->prepare(
        "SELECT COALESCE(SUM(p.amount), 0) FROM payments p
         JOIN events e ON e.id = p.event_id
         WHERE e.organization_id = ? AND p.payment_status = 'approved'"
    );
    $revenue->execute([$organizationId]);
    $totalRevenue = (float) $revenue->fetchColumn();

    $matches = $pdo->prepare(
        'SELECT COUNT(*) FROM matches m
         JOIN events e ON e.id = m.event_id
         WHERE e.organization_id = ?'
    );
    $matches->execute([$organizationId]);
    $matchesMade = (int) $matches->fetchColumn();

    return [
        'total_events' => $totalEvents,
        'total_participants' => $totalParticipants,
        'pending_payments' => $pendingPayments,
        'total_revenue' => $totalRevenue,
        'matches_made' => $matchesMade,
    ];
}

function admin_stats(): array
{
    $pdo = db();

    return [
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'organizations' => (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
        'events' => (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
        'payments_pending' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn(),
        'matches' => (int) $pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn(),
        'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'approved'")->fetchColumn(),
    ];
}

function admin_chart_series(int $days = 7): array
{
    $series = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[$date] = 0;
    }

    return $series;
}

function admin_dashboard_data(): array
{
    $pdo = db();
    $stats = admin_stats();

    $stats['participants'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'participant'")->fetchColumn();
    $stats['organizers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'")->fetchColumn();
    $stats['registrations'] = (int) $pdo->query('SELECT COUNT(*) FROM event_participants')->fetchColumn();
    $stats['live_events'] = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('live', 'paused')")->fetchColumn();
    $stats['published_events'] = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
    $stats['tickets_issued'] = (int) $pdo->query('SELECT COUNT(*) FROM tickets')->fetchColumn();
    $stats['checkins'] = (int) $pdo->query('SELECT COUNT(*) FROM tickets WHERE checked_in = 1')->fetchColumn();
    $stats['revenue_week'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
    )->fetchColumn();

    $revenueByDay = admin_chart_series();
    $revenueRows = $pdo->query(
        "SELECT DATE(created_at) AS day, COALESCE(SUM(amount), 0) AS total
         FROM payments
         WHERE payment_status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(created_at)"
    )->fetchAll();
    foreach ($revenueRows as $row) {
        if (isset($revenueByDay[$row['day']])) {
            $revenueByDay[$row['day']] = (float) $row['total'];
        }
    }

    $registrationsByDay = admin_chart_series();
    $registrationRows = $pdo->query(
        'SELECT DATE(created_at) AS day, COUNT(*) AS total
         FROM event_participants
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(created_at)'
    )->fetchAll();
    foreach ($registrationRows as $row) {
        if (isset($registrationsByDay[$row['day']])) {
            $registrationsByDay[$row['day']] = (int) $row['total'];
        }
    }

    $eventStatusRows = $pdo->query('SELECT status, COUNT(*) AS count FROM events GROUP BY status')->fetchAll();
    $eventStatuses = [];
    foreach ($eventStatusRows as $row) {
        $eventStatuses[$row['status']] = (int) $row['count'];
    }

    $recentEvents = $pdo->query(
        'SELECT e.*, o.name AS org_name,
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
         FROM events e
         JOIN organizations o ON o.id = e.organization_id
         ORDER BY e.event_date DESC, e.created_at DESC
         LIMIT 6'
    )->fetchAll();

    $pendingPayments = $pdo->query(
        "SELECT p.*, u.full_name, e.title AS event_title
         FROM payments p
         JOIN users u ON u.id = p.user_id
         JOIN events e ON e.id = p.event_id
         WHERE p.payment_status = 'pending'
         ORDER BY p.created_at DESC
         LIMIT 5"
    )->fetchAll();

    $recentLogs = $pdo->query(
        'SELECT al.*, u.full_name
         FROM admin_logs al
         JOIN users u ON u.id = al.user_id
         ORDER BY al.created_at DESC
         LIMIT 12'
    )->fetchAll();

    $topOrganizations = $pdo->query(
        "SELECT o.id, o.name,
            COUNT(DISTINCT e.id) AS event_count,
            COALESCE(SUM(CASE WHEN p.payment_status = 'approved' THEN p.amount ELSE 0 END), 0) AS revenue
         FROM organizations o
         LEFT JOIN events e ON e.organization_id = o.id
         LEFT JOIN payments p ON p.event_id = e.id
         GROUP BY o.id, o.name
         ORDER BY revenue DESC, event_count DESC
         LIMIT 5"
    )->fetchAll();

    return [
        'stats' => $stats,
        'revenue_by_day' => $revenueByDay,
        'registrations_by_day' => $registrationsByDay,
        'event_statuses' => $eventStatuses,
        'recent_events' => $recentEvents,
        'pending_payments' => $pendingPayments,
        'recent_logs' => $recentLogs,
        'top_organizations' => $topOrganizations,
    ];
}

function admin_chart_points(array $values, int $width = 320, int $height = 100, int $pad = 8): array
{
    $count = count($values);
    if ($count === 0) {
        return ['path' => '', 'area' => '', 'points' => []];
    }

    $max = max($values) ?: 1;
    $step = $count > 1 ? ($width - $pad * 2) / ($count - 1) : 0;
    $points = [];

    $i = 0;
    foreach ($values as $value) {
        $x = $pad + ($step * $i);
        $y = $height - $pad - (($value / $max) * ($height - $pad * 2));
        $points[] = ['x' => round($x, 1), 'y' => round($y, 1)];
        $i++;
    }

    $line = [];
    foreach ($points as $index => $point) {
        $line[] = ($index === 0 ? 'M' : 'L') . $point['x'] . ',' . $point['y'];
    }

    $first = $points[0];
    $last = $points[count($points) - 1];
    $area = implode(' ', $line)
        . ' L' . $last['x'] . ',' . ($height - $pad)
        . ' L' . $first['x'] . ',' . ($height - $pad)
        . ' Z';

    return [
        'path' => implode(' ', $line),
        'area' => $area,
        'points' => $points,
        'max' => $max,
    ];
}

function admin_action_label(string $action): string
{
    $key = 'admin.actions.' . $action;
    $label = __($key);

    return $label !== $key ? $label : ucwords(str_replace('_', ' ', $action));
}

function user_can_manage_event(int $userId, array $event): bool
{
    if (in_array(current_user_role(), ['admin', 'super_admin'], true)) {
        return true;
    }

    $org = get_organization_for_user($userId);

    return $org && (int) $org['id'] === (int) $event['organization_id'];
}

function default_event_image(): string
{
    return 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=800&q=80';
}

function default_avatar(string $name): string
{
    $initial = strtoupper(substr(trim($name), 0, 1));
    return 'https://ui-avatars.com/api/?name=' . urlencode($initial) . '&background=6C35FF&color=fff&size=128';
}
