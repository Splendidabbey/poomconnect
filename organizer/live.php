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

$pageTitle = 'Live Event Control';
$bodyClass = 'dashboard-page';

$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$eventsList = [];
if ($orgId) {
    $stmt = db()->prepare("SELECT id, title FROM events WHERE organization_id = ? AND status IN ('published','live','paused') ORDER BY event_date DESC");
    $stmt->execute([$orgId]);
    $eventsList = $stmt->fetchAll();
}

$liveState = $event ? get_live_state($eventId) : null;
$rounds = [];

if ($event && $liveState && (int) $liveState['current_round'] > 0) {
    $stmt = db()->prepare(
        'SELECT r.*, ua.full_name AS name_a, ub.full_name AS name_b
         FROM rounds r
         JOIN users ua ON ua.id = r.participant_a
         JOIN users ub ON ub.id = r.participant_b
         WHERE r.event_id = ? AND r.round_number = ?
         ORDER BY r.table_number ASC'
    );
    $stmt->execute([$eventId, $liveState['current_round']]);
    $rounds = $stmt->fetchAll();
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Live Event Control</h1>
                <p>Start, pause, and manage matching rounds</p>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <form method="get">
                <div class="form-group" style="margin:0;">
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
            <?php ensure_live_state($eventId, (int) $event['round_duration']); ?>
            <?php $liveState = get_live_state($eventId); ?>
            <div class="live-status">
                <div class="live-status-item card">
                    <div class="stat-card-label">Current Round</div>
                    <div class="live-status-value" id="current-round"><?= (int) $liveState['current_round'] ?></div>
                </div>
                <div class="live-status-item card">
                    <div class="stat-card-label">Timer (seconds)</div>
                    <div class="live-status-value" id="timer-seconds"><?= (int) $liveState['timer_seconds'] ?></div>
                </div>
                <div class="live-status-item card">
                    <div class="stat-card-label">Event Status</div>
                    <div style="margin-top:0.5rem;"><span class="badge badge-purple" id="event-status"><?= e(ucfirst($liveState['event_status'])) ?></span></div>
                </div>
            </div>

            <div class="live-controls">
                <button class="btn btn-primary btn-lg" onclick="liveAction('start')">▶ Start Event</button>
                <button class="btn btn-outline btn-lg" onclick="liveAction('pause')">⏸ Pause</button>
                <button class="btn btn-primary btn-lg" onclick="liveAction('next')">⏭ Next Round</button>
                <button class="btn btn-outline btn-lg" data-confirm="End this event?" onclick="liveAction('end')">⏹ End Event</button>
            </div>

            <div class="card">
                <h3 style="margin-bottom:1rem;">Round Pairings <?= $liveState['current_round'] > 0 ? '(Round ' . (int) $liveState['current_round'] . ')' : '' ?></h3>
                <?php if ($rounds): ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr><th>Table</th><th>Participant A</th><th>Participant B</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rounds as $r): ?>
                                    <tr>
                                        <td><?= (int) $r['table_number'] ?></td>
                                        <td><?= e($r['name_a']) ?></td>
                                        <td><?= e($r['name_b']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><p>Start the event to generate round pairings.</p></div>
                <?php endif; ?>
            </div>

            <script>
            const eventId = <?= $eventId ?>;
            async function liveAction(action) {
                const endpoints = {
                    start: '<?= base_url('api/start-event.php') ?>',
                    pause: '<?= base_url('api/pause-event.php') ?>',
                    next: '<?= base_url('api/next-round.php') ?>',
                    end: '<?= base_url('api/end-event.php') ?>',
                };
                const res = await apiPost(endpoints[action], { event_id: eventId });
                alert(res.message);
                if (res.success) location.reload();
            }
            </script>
        <?php else: ?>
            <div class="empty-state card"><p>Select an event to control live matching.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
