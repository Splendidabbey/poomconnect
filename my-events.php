<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_member();

$user = current_user();
$userId = (int) $user['id'];
$pageTitle = __('my_events.title');
$registrations = get_user_registrations($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_event_id'])) {
    csrf_verify_or_redirect();

    $eventId = (int) $_POST['cancel_event_id'];
    if (cancel_event_registration($eventId, $userId)) {
        set_flash('success', __('my_events.cancelled'));
    } else {
        set_flash('error', __('my_events.cancel_failed'));
    }
    redirect(base_url('my-events.php'));
}

$today = date('Y-m-d');
$upcoming = [];
$past = [];
foreach ($registrations as $reg) {
    if (($reg['event_date'] ?? '') >= $today) {
        $upcoming[] = $reg;
    } else {
        $past[] = $reg;
    }
}

$nextEvent = $upcoming[0] ?? null;
$otherUpcoming = array_values(array_filter(
    $upcoming,
    static fn(array $reg): bool => !$nextEvent || (int) $reg['event_id'] !== (int) $nextEvent['event_id']
));
$matches = get_user_all_matches($userId);
$rooms = get_user_chat_rooms($userId);
$recentMatches = array_slice($matches, 0, 4);
$firstName = explode(' ', trim((string) ($user['full_name'] ?? '')))[0] ?: __('nav.profile');
$points = (int) ($user['loyalty_points'] ?? 0);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();

$renderRegActions = static function (array $reg): void {
    ?>
    <div class="registration-actions">
        <?php if ($reg['registration_status'] === 'registered' && $reg['payment_status'] === 'approved'): ?>
            <a href="<?= base_url('ticket.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-primary btn-sm"><?php _e('my_events.view_ticket'); ?></a>
            <a href="<?= base_url('participant/live.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-outline btn-sm"><?php _e('my_events.live'); ?></a>
        <?php elseif ($reg['registration_status'] === 'registered' && in_array($reg['payment_status'], ['pending', 'rejected'], true)): ?>
            <a href="<?= base_url('pay.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-primary btn-sm"><?php _e('my_events.pay'); ?></a>
        <?php elseif ($reg['registration_status'] === 'waitlist'): ?>
            <span class="badge badge-outline"><?php _e('my_events.on_waitlist'); ?></span>
        <?php endif; ?>
        <?php if ($reg['registration_status'] !== 'cancelled'): ?>
            <form method="post" onsubmit="return confirm(<?= json_encode(__('my_events.cancel_confirm')) ?>);">
                <?= csrf_field() ?>
                <input type="hidden" name="cancel_event_id" value="<?= (int) $reg['event_id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?php _e('my_events.cancel'); ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?php
};
?>

<section class="dash-hero">
    <div class="container">
        <div class="dash-hero-copy">
            <p class="dash-kicker"><?php _e('my_events.title'); ?></p>
            <h1><?= e(__('my_events.greeting', ['name' => $firstName])) ?></h1>
            <p><?php _e('my_events.home_subtitle'); ?></p>
        </div>
        <div class="dash-stats">
            <a class="dash-stat" href="#your-nights">
                <span><?php _e('my_events.stat_upcoming'); ?></span>
                <strong><?= count($upcoming) ?></strong>
            </a>
            <a class="dash-stat" href="<?= base_url('participant/matches.php') ?>">
                <span><?php _e('my_events.stat_matches'); ?></span>
                <strong><?= count($matches) ?></strong>
            </a>
            <a class="dash-stat" href="<?= base_url('chat.php') ?>">
                <span><?php _e('my_events.stat_chats'); ?></span>
                <strong><?= count($rooms) ?></strong>
            </a>
            <a class="dash-stat" href="<?= base_url('loyalty.php') ?>">
                <span><?php _e('my_events.stat_points'); ?></span>
                <strong><?= $points ?></strong>
            </a>
        </div>
    </div>
</section>

<section class="section content-section dash-section" id="your-nights">
    <div class="container">
        <?php if ($nextEvent): ?>
            <?php
            $nextCover = !empty($nextEvent['cover_image']) ? upload_url($nextEvent['cover_image']) : default_event_image();
            $nextUrl = event_url(['id' => $nextEvent['event_id'], 'slug' => $nextEvent['slug'] ?? '']);
            ?>
            <article class="dash-next">
                <div class="dash-next-media">
                    <img src="<?= e($nextCover) ?>" alt="">
                    <span class="dash-next-label"><?php _e('my_events.next_up'); ?></span>
                </div>
                <div class="dash-next-body">
                    <h2><a href="<?= e($nextUrl) ?>"><?= e($nextEvent['title']) ?></a></h2>
                    <p><?= e(format_date($nextEvent['event_date'])) ?> · <?= e(format_time($nextEvent['start_time'])) ?> · <?= e($nextEvent['city'] ?: ($nextEvent['location'] ?? '')) ?></p>
                    <div class="registration-badges">
                        <span class="badge badge-purple"><?= e(status_label($nextEvent['registration_status'])) ?></span>
                        <span class="badge badge-<?= $nextEvent['payment_status'] === 'approved' ? 'success' : 'warning' ?>"><?= e(status_label($nextEvent['payment_status'])) ?></span>
                    </div>
                    <?php $renderRegActions($nextEvent); ?>
                </div>
            </article>
        <?php elseif ($registrations === []): ?>
            <div class="dash-empty">
                <div class="dash-empty-orb" aria-hidden="true"></div>
                <h2><?php _e('my_events.empty_title'); ?></h2>
                <p><?php _e('my_events.empty_body'); ?></p>
                <div class="dash-empty-actions">
                    <a href="<?= base_url('events.php') ?>" class="btn btn-primary"><?php _e('my_events.browse'); ?></a>
                    <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-outline"><?php _e('my_events.action_host'); ?></a>
                </div>
            </div>
        <?php endif; ?>

        <div class="dash-grid<?= ($otherUpcoming === [] && $past === []) ? ' dash-grid-solo' : '' ?>">
            <?php if ($otherUpcoming !== [] || $past !== []): ?>
            <div class="dash-main">
                <div class="dash-heading">
                    <h2><?php _e('my_events.your_nights'); ?></h2>
                    <a href="<?= base_url('events.php') ?>"><?php _e('my_events.browse'); ?></a>
                </div>

                <?php if ($otherUpcoming !== []): ?>
                    <div class="registrations-list">
                        <?php foreach ($otherUpcoming as $reg): ?>
                            <article class="card registration-card">
                                <div class="registration-main">
                                    <h3><a href="<?= e(event_url(['id' => $reg['event_id'], 'slug' => $reg['slug'] ?? ''])) ?>"><?= e($reg['title']) ?></a></h3>
                                    <p><?= e(format_date($reg['event_date'])) ?> · <?= e(format_time($reg['start_time'])) ?> · <?= e($reg['city'] ?: ($reg['location'] ?? '')) ?></p>
                                    <div class="registration-badges">
                                        <span class="badge badge-purple"><?= e(status_label($reg['registration_status'])) ?></span>
                                        <span class="badge badge-<?= $reg['payment_status'] === 'approved' ? 'success' : 'warning' ?>"><?= e(status_label($reg['payment_status'])) ?></span>
                                    </div>
                                </div>
                                <?php $renderRegActions($reg); ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($past): ?>
                    <h3 class="dash-subhead"><?php _e('my_events.past_nights'); ?></h3>
                    <div class="registrations-list dash-past">
                        <?php foreach ($past as $reg): ?>
                            <article class="card registration-card">
                                <div class="registration-main">
                                    <h3><a href="<?= e(event_url(['id' => $reg['event_id'], 'slug' => $reg['slug'] ?? ''])) ?>"><?= e($reg['title']) ?></a></h3>
                                    <p><?= e(format_date($reg['event_date'])) ?> · <?= e($reg['city'] ?: ($reg['location'] ?? '')) ?></p>
                                </div>
                                <a href="<?= base_url('participant/matches.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-outline btn-sm"><?php _e('nav.matches'); ?></a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <aside class="dash-side">
                <div class="card dash-actions-card">
                    <h2><?php _e('my_events.quick_actions'); ?></h2>
                    <a href="<?= base_url('events.php') ?>" class="dash-action">
                        <span class="dash-action-icon" aria-hidden="true">✦</span>
                        <span>
                            <strong><?php _e('my_events.action_browse'); ?></strong>
                            <em><?php _e('my_events.action_browse_hint'); ?></em>
                        </span>
                    </a>
                    <a href="<?= base_url('organizer/create-event.php') ?>" class="dash-action">
                        <span class="dash-action-icon" aria-hidden="true">＋</span>
                        <span>
                            <strong><?php _e('my_events.action_host'); ?></strong>
                            <em><?php _e('my_events.action_host_hint'); ?></em>
                        </span>
                    </a>
                    <a href="<?= base_url('participant/matches.php') ?>" class="dash-action">
                        <span class="dash-action-icon" aria-hidden="true">♡</span>
                        <span>
                            <strong><?php _e('my_events.action_matches'); ?></strong>
                            <em><?php _e('my_events.action_matches_hint'); ?></em>
                        </span>
                    </a>
                </div>

                <div class="card dash-matches-card">
                    <div class="dash-heading">
                        <h2><?php _e('my_events.recent_matches'); ?></h2>
                        <a href="<?= base_url('participant/matches.php') ?>"><?php _e('my_events.see_all'); ?></a>
                    </div>
                    <?php if ($recentMatches): ?>
                        <ul class="dash-match-list">
                            <?php foreach ($recentMatches as $m): ?>
                                <?php
                                $avatar = $m['partner_avatar'] ? upload_url($m['partner_avatar']) : default_avatar($m['partner_name']);
                                $matchEventId = (int) ($m['event_id'] ?? 0);
                                $room = $matchEventId ? get_chat_room_for_users($matchEventId, $userId, (int) $m['partner_id']) : null;
                                ?>
                                <li>
                                    <img src="<?= e($avatar) ?>" alt="">
                                    <span>
                                        <strong><?= e($m['partner_name']) ?></strong>
                                        <?php if (!empty($m['event_title'])): ?>
                                            <em><?= e($m['event_title']) ?></em>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($room): ?>
                                        <a href="<?= base_url('chat-thread.php?room=' . (int) $room['id']) ?>" class="btn btn-outline btn-sm"><?php _e('my_events.open_chat'); ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="form-help"><?php _e('my_events.none_recent'); ?></p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
