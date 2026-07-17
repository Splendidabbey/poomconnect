<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_login(['participant', 'organizer', 'admin', 'super_admin']);

$userId = (int) current_user()['id'];
$user = get_user_profile($userId);
$badges = get_user_badges($userId);
$levelInfo = loyalty_level_info((int) ($user['loyalty_points'] ?? 0));

$pageTitle = __('loyalty.title');

require_once APP_ROOT . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('loyalty.title'); ?></h1>
        <?php if (!empty($user['is_vip'])): ?><span class="badge badge-purple">VIP</span><?php endif; ?>
    </div>
</section>

<section class="section content-section">
    <div class="container">
        <div class="stats-grid">
            <div class="card stat-card"><span><?php _e('loyalty.points'); ?></span><strong><?= (int) ($user['loyalty_points'] ?? 0) ?></strong></div>
            <div class="card stat-card"><span><?php _e('loyalty.level'); ?></span><strong><?= e(ucfirst($levelInfo['level'])) ?></strong></div>
            <div class="card stat-card"><span><?php _e('loyalty.credits'); ?></span><strong><?= (int) ($user['referral_credits'] ?? 0) ?></strong></div>
        </div>

        <?php if ($levelInfo['next']): ?>
        <div class="card" style="margin:1.5rem 0;">
            <p><?= e(__('loyalty.progress', ['next' => $levelInfo['next'], 'pct' => $levelInfo['progress']])) ?></p>
            <div class="loyalty-progress"><div class="loyalty-progress-bar" style="width:<?= (int) $levelInfo['progress'] ?>%"></div></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php _e('loyalty.badges'); ?></h2>
            <?php if ($badges): ?>
                <div class="badge-grid">
                    <?php foreach ($badges as $b): ?>
                        <span class="badge badge-purple"><?= e(__('loyalty.badge.' . $b['badge_key'])) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('loyalty.no_badges'); ?></p>
            <?php endif; ?>
        </div>

        <p class="form-help"><?php _e('loyalty.earn_hint'); ?></p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
