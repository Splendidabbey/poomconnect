<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant']);

$userId = (int) current_user()['id'];
$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$event = get_event_by_id($eventId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $roundId = (int) ($_POST['round_id'] ?? 0);
    $targetId = (int) ($_POST['target_id'] ?? 0);
    $vote = trim($_POST['vote'] ?? '');
    if ($roundId && $targetId && in_array($vote, ['like', 'friend', 'business', 'pass'], true)) {
        process_match_votes($eventId, $roundId, $userId, $targetId, $vote);
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    redirect(base_url('participant/live.php?event_id=' . $eventId));
}

if (!$event) {
    redirect(base_url('my-events.php'));
}

$reg = get_user_event_registration($eventId, $userId);
if (!$reg || $reg['payment_status'] !== 'approved') {
    redirect(base_url('my-events.php'));
}

$pageTitle = __('live_page.title');
$liveState = get_live_state($eventId);
$initial = get_live_state_payload($eventId, $userId);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="live-participant-page">
    <div class="container">
        <div class="live-top-bar">
            <div>
                <h1><?= e($event['title']) ?></h1>
                <span class="badge badge-purple" id="live-status"><?= e(status_label($liveState['event_status'] ?? 'waiting')) ?></span>
            </div>
            <div class="live-round-timer">
                <div class="live-round-block">
                    <span><?php _e('live_page.round_label'); ?></span>
                    <strong id="live-round"><?= (int) $initial['round'] ?></strong>
                </div>
                <div class="live-timer-block">
                    <span><?php _e('live_page.timer_label'); ?></span>
                    <strong id="live-timer" class="live-countdown"><?= gmdate('i:s', (int) $initial['timer']) ?></strong>
                </div>
            </div>
        </div>

        <div id="live-broadcast" class="live-broadcast-banner" <?= empty($initial['broadcast']) ? 'hidden' : '' ?>>
            📢 <span id="live-broadcast-text"><?= e($initial['broadcast'] ?? '') ?></span>
        </div>

        <div class="live-participant-grid">
            <div class="card match-card live-partner-card" id="partner-card">
                <h2><?php _e('live_page.partner'); ?></h2>
                <div id="partner-content">
                    <?php if (!empty($initial['partner'])): ?>
                        <div class="match-profile">
                            <img src="<?= e($initial['partner']['avatar']) ?>" alt="" class="organizer-avatar" id="partner-avatar">
                            <div>
                                <strong id="partner-name"><?= e($initial['partner']['name']) ?></strong>
                                <p id="partner-occupation"><?= e($initial['partner']['occupation']) ?></p>
                                <div class="compatibility-score" id="partner-compat"><?= e(__('live_page.compatibility', ['score' => $initial['compatibility']])) ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p id="waiting-msg"><?php _e('live_page.waiting'); ?></p>
                    <?php endif; ?>
                </div>

                <div id="icebreaker-block" <?= empty($initial['starters']) ? 'hidden' : '' ?>>
                    <h3><?php _e('live_page.icebreaker'); ?></h3>
                    <ul class="starter-list" id="starter-list">
                        <?php foreach ($initial['starters'] as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
                    </ul>
                </div>

                <div id="vote-block" <?= empty($initial['round_id']) ? 'hidden' : '' ?>>
                    <p><?php _e('live_page.vote_prompt'); ?></p>
                    <div class="vote-buttons vote-buttons-lg">
                        <button type="button" class="btn btn-primary" data-vote="like">❤️ <?php _e('live_page.vote_like'); ?></button>
                        <button type="button" class="btn btn-outline" data-vote="pass">👋 <?php _e('live_page.vote_pass'); ?></button>
                        <button type="button" class="btn btn-outline" data-vote="business">💼 <?php _e('live_page.vote_business'); ?></button>
                        <button type="button" class="btn btn-outline" data-vote="friend">🤝 <?php _e('live_page.vote_friend'); ?></button>
                    </div>
                </div>
            </div>

            <aside class="card">
                <h2><?php _e('live_page.best_matches'); ?></h2>
                <div id="best-matches" class="best-matches-list"></div>
                <a href="<?= base_url('participant/matches.php?event_id=' . $eventId) ?>" class="btn btn-outline btn-sm" style="margin-top:1rem;"><?php _e('matches.view_all'); ?></a>
            </aside>
        </div>
    </div>
</section>

<script>
const liveEventId = <?= $eventId ?>;
const liveStateUrl = '<?= base_url('api/live-state.php') ?>?event_id=' + liveEventId;
let roundId = <?= (int) ($initial['round_id'] ?? 0) ?>;
let partnerId = <?= (int) ($initial['partner']['id'] ?? 0) ?>;
let timerSec = <?= (int) $initial['timer'] ?>;

async function pollLive() {
    try {
        const res = await fetch(liveStateUrl);
        const data = await res.json();
        if (!data.success) return;
        const live = data.live;
        document.getElementById('live-round').textContent = live.round;
        document.getElementById('live-status').textContent = live.status;
        timerSec = live.timer;
        if (live.broadcast) {
            document.getElementById('live-broadcast').hidden = false;
            document.getElementById('live-broadcast-text').textContent = live.broadcast;
        }
        if (live.partner) {
            document.getElementById('waiting-msg')?.remove();
            partnerId = live.partner.id;
            roundId = live.round_id;
            document.getElementById('vote-block').hidden = false;
            document.getElementById('icebreaker-block').hidden = false;
        }
        if (live.emergency) {
            document.getElementById('live-broadcast-text').textContent = <?= json_encode(__('realtime.emergency_stopped')) ?>;
            document.getElementById('live-broadcast').hidden = false;
        }
    } catch (e) {}
}

document.querySelectorAll('[data-vote]').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!roundId || !partnerId) return;
        const fd = new FormData();
        fd.append('event_id', liveEventId);
        fd.append('round_id', roundId);
        fd.append('target_id', partnerId);
        fd.append('vote', btn.dataset.vote);
        await fetch(location.pathname + location.search, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        btn.classList.add('is-voted');
    });
});

setInterval(pollLive, 4000);
setInterval(() => {
    if (timerSec > 0) timerSec--;
    const m = Math.floor(timerSec / 60), s = timerSec % 60;
    document.getElementById('live-timer').textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}, 1000);
pollLive();
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
