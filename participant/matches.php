<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant']);

$userId = (int) current_user()['id'];
$eventId = (int) ($_GET['event_id'] ?? 0);
$event = $eventId ? get_event_by_id($eventId) : null;

$pageTitle = __('matches.title');
$likeMatches = $eventId ? get_user_event_matches($userId, $eventId, 'like') : get_user_all_matches($userId, 'like');
$friendMatches = $eventId ? get_user_event_matches($userId, $eventId, 'friend') : get_user_all_matches($userId, 'friend');
$businessMatches = $eventId ? get_user_event_matches($userId, $eventId, 'business') : get_user_all_matches($userId, 'business');

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('matches.title'); ?></h1>
        <p><?= $event ? e($event['title']) : e(__('matches.all_events')) ?></p>
    </div>
</section>

<section class="section content-section">
    <div class="container">
        <?php
        $sections = [
            'like' => ['title' => __('matches.mutual'), 'items' => $likeMatches, 'icon' => '❤️'],
            'friend' => ['title' => __('matches.friends'), 'items' => $friendMatches, 'icon' => '🤝'],
            'business' => ['title' => __('matches.business'), 'items' => $businessMatches, 'icon' => '💼'],
        ];
        foreach ($sections as $type => $section):
        ?>
        <div class="card match-reveal-section">
            <h2><?= $section['icon'] ?> <?= e($section['title']) ?></h2>
            <?php if ($section['items']): ?>
                <div class="match-reveal-grid">
                    <?php foreach ($section['items'] as $m): ?>
                        <?php
                        $matchEventId = (int) ($m['event_id'] ?? $eventId);
                        $room = get_chat_room_for_users($matchEventId, $userId, (int) $m['partner_id']);
                        $avatar = $m['partner_avatar'] ? upload_url($m['partner_avatar']) : default_avatar($m['partner_name']);
                        ?>
                        <div class="match-reveal-card">
                            <img src="<?= e($avatar) ?>" alt="">
                            <strong><?= e($m['partner_name']) ?></strong>
                            <?php if (!$eventId && !empty($m['event_title'])): ?>
                                <span class="form-help"><?= e($m['event_title']) ?></span>
                            <?php endif; ?>
                            <?php if ($room): ?>
                                <a href="<?= base_url('chat.php?room=' . (int) $room['id']) ?>" class="btn btn-primary btn-sm"><?php _e('matches.chat_unlocked'); ?></a>
                            <?php else: ?>
                                <span class="badge badge-outline"><?php _e('matches.chat_pending'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="form-help"><?php _e('matches.none_yet'); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <a href="<?= base_url('my-events.php') ?>" class="btn btn-outline"><?php _e('matches.back'); ?></a>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
