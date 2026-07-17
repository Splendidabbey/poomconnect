<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$org = get_organization_for_user((int) current_user()['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('organizer.analytics_title');
$bodyClass = 'dashboard-page';
$stats = organizer_analytics_detailed((int) $org['id']);
$ai = ai_event_suggestions((int) $org['id']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="admin-main dashboard-main">
        <div class="dashboard-header"><h1><?php _e('organizer.analytics_title'); ?></h1></div>

        <div class="stats-grid">
            <div class="card stat-card"><span><?php _e('organizer.total_revenue'); ?></span><strong><?= e(format_currency($stats['revenue'])) ?></strong></div>
            <div class="card stat-card"><span><?php _e('analytics.participants'); ?></span><strong><?= (int) $stats['participants'] ?></strong></div>
            <div class="card stat-card"><span><?php _e('analytics.attendance'); ?></span><strong><?= e($stats['attendance_rate']) ?>%</strong></div>
            <div class="card stat-card"><span><?php _e('analytics.match_rate'); ?></span><strong><?= e($stats['match_rate']) ?>%</strong></div>
            <div class="card stat-card"><span><?php _e('analytics.conversion'); ?></span><strong><?= e($stats['conversion']) ?></strong></div>
            <div class="card stat-card"><span><?php _e('analytics.retention'); ?></span><strong><?= (int) $stats['retention'] ?>%</strong></div>
            <div class="card stat-card"><span><?php _e('analytics.referrals'); ?></span><strong><?= (int) $stats['referral_uses'] ?></strong></div>
            <div class="card stat-card"><span><?php _e('organizer.matches_made'); ?></span><strong><?= (int) $stats['matches'] ?></strong></div>
        </div>

        <?php if ($stats['gender_breakdown']): ?>
        <div class="card" style="margin-top:1.5rem;">
            <h2><?php _e('analytics.gender_ratio'); ?></h2>
            <ul>
                <?php foreach ($stats['gender_breakdown'] as $g): ?>
                    <li><?= e(__('gender.' . ($g['gender'] ?? 'other'))) ?>: <?= (int) $g['cnt'] ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($stats['popular_events']): ?>
        <div class="card" style="margin-top:1.5rem;">
            <h2><?php _e('analytics.popular_events'); ?></h2>
            <table class="table">
                <thead><tr><th><?php _e('organizer.event'); ?></th><th><?php _e('analytics.participants'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($stats['popular_events'] as $ev): ?>
                        <tr><td><?= e($ev['title']) ?></td><td><?= (int) $ev['participants'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="card ai-insights-card" style="margin-top:1.5rem;">
            <h2>✨ <?php _e('ai_analytics.title'); ?></h2>
            <p class="form-help"><?= e(__('ai_analytics.confidence', ['pct' => $ai['confidence'] ?? 70])) ?></p>
            <div class="ai-insights-grid">
                <div><strong><?php _e('ai_analytics.ticket_price'); ?></strong><p><?= e(format_currency((float) $ai['ticket_price'])) ?></p></div>
                <div><strong><?php _e('ai_analytics.best_time'); ?></strong><p><?= e($ai['best_time']) ?> · <?= e($ai['best_day']) ?></p></div>
                <div><strong><?php _e('ai_analytics.capacity'); ?></strong><p><?= (int) $ai['capacity'] ?> <?php _e('analytics.seats'); ?></p></div>
                <div><strong><?php _e('ai_analytics.venue'); ?></strong><p><?= e($ai['venue_type']) ?></p></div>
                <div><strong><?php _e('ai_analytics.audience'); ?></strong><p><?= e($ai['audience']) ?></p></div>
                <div><strong><?php _e('ai_analytics.marketing'); ?></strong><p><?= e($ai['marketing']) ?></p></div>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
