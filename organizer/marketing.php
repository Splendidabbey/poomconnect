<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('marketing.title');
$bodyClass = 'dashboard-page';
$orgId = (int) $org['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db()->prepare('UPDATE organizations SET tiktok_handle = ?, seo_keywords = ? WHERE id = ?')->execute([
        trim($_POST['tiktok_handle'] ?? '') ?: null,
        trim($_POST['seo_keywords'] ?? '') ?: null,
        $orgId,
    ]);
    set_flash('success', __('marketing.saved'));
    redirect(base_url('organizer/marketing.php'));
}

$stats = marketing_stats($orgId);
$latestEvent = db()->prepare('SELECT id, title FROM events WHERE organization_id = ? AND status = \'published\' ORDER BY event_date DESC LIMIT 1');
$latestEvent->execute([$orgId]);
$latestEvent = $latestEvent->fetch();
$shareUrl = $latestEvent ? event_url(get_event_by_id((int) $latestEvent['id'])) : org_public_url($org);
$shareTitle = $latestEvent['title'] ?? org_brand_name($org);
$tiktokCaption = tiktok_share_caption($shareTitle, $shareUrl, $org['tiktok_handle'] ?? null);

$cards = [
    ['title' => __('marketing.landing'), 'desc' => __('marketing.landing_desc'), 'url' => base_url('organizer/branding.php'), 'icon' => '🏠'],
    ['title' => __('marketing.seo'), 'desc' => __('marketing.seo_desc'), 'url' => base_url('organizer/events.php'), 'icon' => '🔍'],
    ['title' => __('marketing.blog'), 'desc' => __('marketing.blog_desc'), 'url' => base_url('organizer/blog.php'), 'icon' => '📝'],
    ['title' => __('marketing.referrals'), 'desc' => __('marketing.referrals_desc'), 'url' => base_url('organizer/referrals.php'), 'icon' => '🔗'],
    ['title' => __('marketing.coupons'), 'desc' => __('marketing.coupons_desc'), 'url' => base_url('organizer/coupons.php'), 'icon' => '🏷️'],
    ['title' => __('marketing.email_campaigns'), 'desc' => __('marketing.email_desc'), 'url' => base_url('organizer/email-campaigns.php'), 'icon' => '✉️'],
];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <h1><?php _e('marketing.title'); ?></h1>
            <p class="text-muted"><?php _e('marketing.sub'); ?></p>
        </div>

        <div class="stat-grid marketing-stat-grid">
            <div class="stat-card"><span class="stat-value"><?= (int) $stats['campaigns'] ?></span><span class="stat-label"><?php _e('marketing.stats_campaigns'); ?></span></div>
            <div class="stat-card"><span class="stat-value"><?= (int) $stats['emails_sent'] ?></span><span class="stat-label"><?php _e('marketing.stats_emails'); ?></span></div>
            <div class="stat-card"><span class="stat-value"><?= (int) $stats['social_shares'] ?></span><span class="stat-label"><?php _e('marketing.stats_shares'); ?></span></div>
            <div class="stat-card"><span class="stat-value"><?= (int) $stats['coupons'] ?></span><span class="stat-label"><?php _e('marketing.stats_coupons'); ?></span></div>
            <div class="stat-card"><span class="stat-value"><?= (int) $stats['referral_uses'] ?></span><span class="stat-label"><?php _e('marketing.stats_referrals'); ?></span></div>
        </div>

        <div class="marketing-grid">
            <?php foreach ($cards as $card): ?>
                <a href="<?= e($card['url']) ?>" class="card marketing-card">
                    <span class="marketing-card-icon"><?= $card['icon'] ?></span>
                    <h3><?= e($card['title']) ?></h3>
                    <p><?= e($card['desc']) ?></p>
                    <span class="marketing-card-link"><?php _e('marketing.manage'); ?> →</span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <h2><?php _e('marketing.tiktok'); ?></h2>
            <form method="post" class="form-grid-2">
                <div class="form-group">
                    <label><?php _e('marketing.tiktok_handle'); ?></label>
                    <input class="input" name="tiktok_handle" value="<?= e($org['tiktok_handle'] ?? '') ?>" placeholder="@yourhandle">
                    <p class="form-help"><?php _e('marketing.tiktok_handle_help'); ?></p>
                </div>
                <div class="form-group">
                    <label><?php _e('marketing.seo_keywords'); ?></label>
                    <input class="input" name="seo_keywords" value="<?= e($org['seo_keywords'] ?? '') ?>" placeholder="events, bangkok, networking">
                    <p class="form-help"><?php _e('marketing.seo_keywords_help'); ?></p>
                </div>
                <div class="form-group form-group-full">
                    <button type="submit" class="btn btn-primary"><?php _e('common.save'); ?></button>
                </div>
            </form>
            <?php if ($latestEvent): ?>
                <div class="tiktok-preview">
                    <label><?php _e('marketing.tiktok_caption'); ?></label>
                    <textarea class="input" readonly rows="3"><?= e($tiktokCaption) ?></textarea>
                    <a href="<?= e(social_share_url('tiktok', $shareUrl, $shareTitle)) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener"><?php _e('marketing.open_tiktok'); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <h2><?php _e('marketing.seo_tips'); ?></h2>
            <ul class="seo-checklist">
                <li><?php _e('marketing.seo_tip_meta'); ?></li>
                <li><?php _e('marketing.seo_tip_blog'); ?></li>
                <li><?php _e('marketing.seo_tip_social'); ?></li>
            </ul>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <h2><?php _e('marketing.social'); ?></h2>
            <p class="text-muted"><?php _e('marketing.social_desc'); ?></p>
            <?= render_social_share([
                'url' => $shareUrl,
                'title' => $shareTitle,
                'entity_type' => $latestEvent ? 'event' : 'org',
                'entity_id' => $latestEvent ? (int) $latestEvent['id'] : $orgId,
            ]) ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
