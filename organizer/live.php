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

$pageTitle = __('organizer.live_title');
$bodyClass = 'dashboard-page';
$hideNav = false;

$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$eventsList = [];
if ($orgId) {
    $stmt = db()->prepare("SELECT id, title FROM events WHERE organization_id = ? AND status IN ('published','live','paused','completed') ORDER BY event_date DESC");
    $stmt->execute([$orgId]);
    $eventsList = $stmt->fetchAll();
}

$liveState = $event ? get_live_state($eventId) : null;
$rounds = [];
$broadcasts = [];

if ($event && $liveState && (int) $liveState['current_round'] > 0) {
    $stmt = db()->prepare(
        'SELECT r.*, ua.full_name AS name_a, ub.full_name AS name_b
         FROM rounds r JOIN users ua ON ua.id = r.participant_a JOIN users ub ON ub.id = r.participant_b
         WHERE r.event_id = ? AND r.round_number = ? ORDER BY r.table_number ASC'
    );
    $stmt->execute([$eventId, $liveState['current_round']]);
    $rounds = $stmt->fetchAll();
}

if ($event) {
    $b = db()->prepare('SELECT eb.*, u.full_name FROM event_broadcasts eb JOIN users u ON u.id = eb.sender_id WHERE eb.event_id = ? ORDER BY eb.created_at DESC LIMIT 10');
    $b->execute([$eventId]);
    $broadcasts = $b->fetchAll();
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('realtime.engine_title'); ?></h1></div>

        <div class="card" style="margin-bottom:1.5rem;">
            <form method="get">
                <select name="event_id" class="select" onchange="this.form.submit()">
                    <option value=""><?php _e('organizer.select_event'); ?></option>
                    <?php foreach ($eventsList as $ev): ?>
                        <option value="<?= (int) $ev['id'] ?>" <?= $eventId === (int) $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($event): ?>
            <?php ensure_live_state($eventId, (int) $event['round_duration']); ?>
            <?php $liveState = get_live_state($eventId); ?>
            <?php $timerRemaining = live_timer_remaining($liveState); ?>

            <div class="live-engine-grid">
                <div class="card live-engine-panel">
                    <h3><?php _e('realtime.current_round'); ?></h3>
                    <div class="live-engine-stat" id="org-round"><?= (int) $liveState['current_round'] ?></div>
                </div>
                <div class="card live-engine-panel">
                    <h3><?php _e('realtime.countdown'); ?></h3>
                    <div class="live-engine-timer" id="org-timer" data-seconds="<?= $timerRemaining ?>"><?= gmdate('i:s', $timerRemaining) ?></div>
                </div>
                <div class="card live-engine-panel">
                    <h3><?php _e('common.status'); ?></h3>
                    <span class="badge badge-purple" id="org-status"><?= e(status_label($liveState['event_status'])) ?></span>
                </div>
            </div>

            <div class="live-controls live-controls-extended">
                <button class="btn btn-primary btn-lg" onclick="liveAction('start')">▶ <?php _e('organizer.start_event'); ?></button>
                <button class="btn btn-outline btn-lg" onclick="liveAction('pause')">⏸ <?php _e('organizer.pause_event'); ?></button>
                <button class="btn btn-outline btn-lg" onclick="liveAction('resume')">▶ <?php _e('realtime.resume'); ?></button>
                <button class="btn btn-primary btn-lg" onclick="liveAction('next')">⏭ <?php _e('organizer.next_round'); ?></button>
                <button class="btn btn-outline btn-lg" onclick="liveAction('end')">⏹ <?php _e('organizer.end_event'); ?></button>
                <button class="btn btn-danger btn-lg" onclick="if(confirm(<?= json_encode(__('realtime.emergency_confirm')) ?>)) liveAction('emergency')">🛑 <?php _e('realtime.emergency_stop'); ?></button>
            </div>

            <div class="card" style="margin-bottom:1.5rem;">
                <h3><?php _e('realtime.broadcast'); ?></h3>
                <form id="broadcast-form" class="form-row" onsubmit="return sendBroadcast(event)">
                    <input type="text" name="message" class="input" placeholder="<?= e(__('realtime.broadcast_placeholder')) ?>" required style="flex:1;">
                    <button type="submit" class="btn btn-primary"><?php _e('realtime.send_broadcast'); ?></button>
                </form>
                <?php if ($broadcasts): ?>
                    <ul class="broadcast-history">
                        <?php foreach ($broadcasts as $b): ?>
                            <li><strong><?= e($b['full_name']) ?>:</strong> <?= e($b['message']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3><?php _e('realtime.rotation'); ?> — <?= e(__('organizer.round')) ?> <?= (int) $liveState['current_round'] ?></h3>
                <?php if ($rounds): ?>
                    <table class="table">
                        <thead><tr><th>Table</th><th>A</th><th>B</th></tr></thead>
                        <tbody>
                            <?php foreach ($rounds as $r): ?>
                                <tr><td><?= (int) $r['table_number'] ?></td><td><?= e($r['name_a']) ?></td><td><?= e($r['name_b']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('organizer.no_pairings'); ?></p>
                <?php endif; ?>
            </div>

            <script>
            const eventId = <?= $eventId ?>;
            const endpoints = {
                start: '<?= base_url('api/start-event.php') ?>',
                pause: '<?= base_url('api/pause-event.php') ?>',
                resume: '<?= base_url('api/resume-event.php') ?>',
                next: '<?= base_url('api/next-round.php') ?>',
                end: '<?= base_url('api/end-event.php') ?>',
                emergency: '<?= base_url('api/emergency-stop.php') ?>',
                broadcast: '<?= base_url('api/broadcast.php') ?>',
            };
            async function liveAction(action) {
                const res = await apiPost(endpoints[action], { event_id: eventId });
                alert(res.message);
                if (res.success) location.reload();
            }
            async function sendBroadcast(e) {
                e.preventDefault();
                const msg = e.target.message.value;
                const res = await apiPost(endpoints.broadcast, { event_id: eventId, message: msg });
                alert(res.message);
                if (res.success) location.reload();
                return false;
            }
            let orgTimer = parseInt(document.getElementById('org-timer')?.dataset.seconds || '0', 10);
            setInterval(() => {
                if (orgTimer > 0) orgTimer--;
                const el = document.getElementById('org-timer');
                if (el) el.textContent = String(Math.floor(orgTimer/60)).padStart(2,'0') + ':' + String(orgTimer%60).padStart(2,'0');
            }, 1000);
            </script>
        <?php else: ?>
            <div class="empty-state card"><p><?php _e('organizer.select_event'); ?></p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
