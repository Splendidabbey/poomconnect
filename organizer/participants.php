<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$eventId = (int) ($_GET['event_id'] ?? 0);
$event = $eventId ? get_event_by_id($eventId) : null;

if ($event && !user_can_manage_event((int) $user['id'], $event)) {
    $event = null;
}

$pageTitle = 'Participants';
$bodyClass = 'dashboard-page';

$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$eventsList = [];
if ($orgId) {
    $stmt = db()->prepare('SELECT id, title FROM events WHERE organization_id = ? ORDER BY event_date DESC');
    $stmt->execute([$orgId]);
    $eventsList = $stmt->fetchAll();
}

$participants = [];
if ($event) {
    $stmt = db()->prepare(
        'SELECT ep.*, u.full_name, u.email, u.phone, t.qr_token, t.checked_in AS ticket_checked_in
         FROM event_participants ep
         JOIN users u ON u.id = ep.user_id
         LEFT JOIN tickets t ON t.event_id = ep.event_id AND t.user_id = ep.user_id
         WHERE ep.event_id = ?
         ORDER BY ep.created_at DESC'
    );
    $stmt->execute([$eventId]);
    $participants = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $qrToken = trim($_POST['qr_token'] ?? '');
    if ($qrToken !== '') {
        $result = checkin_ticket($qrToken);
        if ($result['success']) {
            set_flash('success', $result['message']);
        } elseif (!empty($result['warning'])) {
            set_flash('warning', $result['message']);
        } else {
            set_flash('error', $result['message']);
        }
        redirect(base_url('organizer/participants.php?event_id=' . $eventId));
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Participants</h1>
                <p>Manage registrations and check-ins</p>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <form method="get" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end;">
                <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                    <label for="event_id">Select Event</label>
                    <select id="event_id" name="event_id" class="select" onchange="this.form.submit()">
                        <option value="">Choose event...</option>
                        <?php foreach ($eventsList as $ev): ?>
                            <option value="<?= (int) $ev['id'] ?>" <?= $eventId === (int) $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($event): ?>
            <div class="card" style="margin-bottom:1.5rem;">
                <h3 style="margin-bottom:1rem;">Check In Participant</h3>
                <form method="post" class="form-row">
                    <div class="form-group" style="margin:0;">
                        <label for="qr_token">QR Token</label>
                        <input type="text" id="qr_token" name="qr_token" class="input" placeholder="Scan or enter QR token">
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:end;">Check In</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-bottom:1rem;"><?= e($event['title']) ?> — <?= count($participants) ?> participants</h3>
                <?php if ($participants): ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Payment</th>
                                    <th>Ticket</th>
                                    <th>Checked In</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                    <tr>
                                        <td><?= e($p['full_name']) ?></td>
                                        <td><?= e($p['email']) ?></td>
                                        <td><span class="badge badge-<?= $p['payment_status'] === 'approved' ? 'success' : ($p['payment_status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e(ucfirst($p['payment_status'])) ?></span></td>
                                        <td><?= $p['qr_token'] ? '<span class="badge badge-purple">Issued</span>' : '—' ?></td>
                                        <td><?= (int) $p['checked_in'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-purple">No</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><p>No participants registered yet.</p></div>
                <?php endif; ?>
            </div>
        <?php elseif ($eventId): ?>
            <div class="alert alert-error">Event not found.</div>
        <?php else: ?>
            <div class="empty-state card"><p>Select an event to view participants.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
