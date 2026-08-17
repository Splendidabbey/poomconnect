<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_member();

$userId = (int) current_user()['id'];
$eventId = (int) ($_GET['event_id'] ?? 0);
$event = $eventId ? get_event_by_id($eventId) : null;
$activeType = (string) ($_GET['type'] ?? 'like');
if (!in_array($activeType, ['like', 'friend', 'business'], true)) {
    $activeType = 'like';
}

$pageTitle = __('matches.title');
$likeMatches = $eventId ? get_user_event_matches($userId, $eventId, 'like') : get_user_all_matches($userId, 'like');
$friendMatches = $eventId ? get_user_event_matches($userId, $eventId, 'friend') : get_user_all_matches($userId, 'friend');
$businessMatches = $eventId ? get_user_event_matches($userId, $eventId, 'business') : get_user_all_matches($userId, 'business');

$sections = [
    'like' => [
        'title' => __('matches.mutual'),
        'hint' => __('matches.mutual_hint'),
        'items' => $likeMatches,
        'icon' => 'heart',
    ],
    'friend' => [
        'title' => __('matches.friends'),
        'hint' => __('matches.friends_hint'),
        'items' => $friendMatches,
        'icon' => 'people',
    ],
    'business' => [
        'title' => __('matches.business'),
        'hint' => __('matches.business_hint'),
        'items' => $businessMatches,
        'icon' => 'brief',
    ],
];

$active = $sections[$activeType];
$totalCount = count($likeMatches) + count($friendMatches) + count($businessMatches);

$tabUrl = static function (string $type) use ($eventId): string {
    $query = ['type' => $type];
    if ($eventId) {
        $query['event_id'] = $eventId;
    }

    return base_url('participant/matches.php?' . http_build_query($query));
};

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="dash-hero dash-hero-compact match-hero">
    <div class="container">
        <div class="dash-hero-copy">
            <p class="dash-kicker"><?php _e('nav.matches'); ?></p>
            <h1><?php _e('matches.title'); ?></h1>
            <p><?= $event ? e($event['title']) : e(__('matches.subtitle')) ?></p>
        </div>
        <div class="match-summary">
            <span class="match-summary-count"><?= $totalCount ?></span>
            <span class="match-summary-label"><?php _e('nav.matches'); ?></span>
        </div>
    </div>
</section>

<section class="section content-section dash-section">
    <div class="container match-page">
        <div class="match-tabs" role="tablist" aria-label="<?= e(__('matches.title')) ?>">
            <?php foreach ($sections as $type => $section): ?>
                <a href="<?= e($tabUrl($type)) ?>"
                   class="match-tab<?= $activeType === $type ? ' is-active' : '' ?>"
                   role="tab"
                   aria-selected="<?= $activeType === $type ? 'true' : 'false' ?>">
                    <span class="match-tab-icon match-tab-icon--<?= e($section['icon']) ?>" aria-hidden="true">
                        <?php if ($section['icon'] === 'heart'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M19.5 12.6 12 20l-7.5-7.4A5 5 0 1 1 12 6a5 5 0 1 1 7.5 6.6z"/></svg>
                        <?php elseif ($section['icon'] === 'people'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="9" cy="8" r="3"/><path d="M3 19v-1.2A4.8 4.8 0 0 1 7.8 13h2.4A4.8 4.8 0 0 1 15 17.8V19"/><circle cx="17" cy="8" r="2.4"/><path d="M21 19v-1a3.6 3.6 0 0 0-2.7-3.5"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V6a4 4 0 0 1 8 0v1"/></svg>
                        <?php endif; ?>
                    </span>
                    <span class="match-tab-copy">
                        <strong><?= e($section['title']) ?></strong>
                        <em><?= count($section['items']) ?></em>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="match-panel card">
            <div class="match-panel-head">
                <div>
                    <h2><?= e($active['title']) ?></h2>
                    <p><?= e($active['hint']) ?></p>
                </div>
            </div>

            <?php if ($active['items']): ?>
                <ul class="match-people">
                    <?php foreach ($active['items'] as $m): ?>
                        <?php
                        $matchEventId = (int) ($m['event_id'] ?? $eventId);
                        $room = get_chat_room_for_users($matchEventId, $userId, (int) $m['partner_id']);
                        $avatar = $m['partner_avatar'] ? upload_url($m['partner_avatar']) : default_avatar($m['partner_name']);
                        ?>
                        <li class="match-person">
                            <img src="<?= e($avatar) ?>" alt="">
                            <div class="match-person-copy">
                                <strong><?= e($m['partner_name']) ?></strong>
                                <?php if (!$eventId && !empty($m['event_title'])): ?>
                                    <span><?= e(__('matches.from_event', ['event' => $m['event_title']])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($room): ?>
                                <a href="<?= base_url('chat-thread.php?room=' . (int) $room['id']) ?>" class="btn btn-primary btn-sm"><?php _e('matches.chat_unlocked'); ?></a>
                            <?php else: ?>
                                <span class="match-status"><?php _e('matches.chat_pending'); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="match-empty">
                    <h3><?php _e('matches.empty_title'); ?></h3>
                    <p><?php _e('matches.empty_body'); ?></p>
                    <div class="dash-empty-actions">
                        <a href="<?= base_url('events.php') ?>" class="btn btn-primary"><?php _e('my_events.browse'); ?></a>
                        <a href="<?= base_url('my-events.php') ?>" class="btn btn-outline"><?php _e('matches.back'); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
