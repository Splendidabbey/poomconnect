<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$org = get_organization_for_user((int) current_user()['id']);
if (!$org) {
    redirect(base_url('organizer/settings.php'));
}

ensure_org_subscription((int) $org['id']);
$pageTitle = __('subscription.title');
$current = get_org_subscription((int) $org['id']);
$plans = get_subscription_plans();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_slug'])) {
    assign_org_plan((int) $org['id'], (string) $_POST['plan_slug']);
    set_flash('success', __('subscription.plan_updated'));
    redirect(base_url('organizer/subscription.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('subscription.title'); ?></h1></div>
        <?php if ($current): ?>
            <div class="card" style="margin-bottom:1.5rem;">
                <p><?php _e('subscription.current'); ?>: <strong><?= e($current['plan_name']) ?></strong></p>
                <p><?= e(format_currency((float) ($plans[array_search($current['plan_slug'], array_column($plans, 'slug'), true)]['price_monthly'] ?? 0))) ?> / <?php _e('subscription.month'); ?></p>
            </div>
        <?php endif; ?>
        <div class="plans-grid">
            <?php foreach ($plans as $plan): ?>
                <?php $features = json_decode($plan['features'] ?? '{}', true) ?: []; ?>
                <div class="card plan-card<?= ($current['plan_slug'] ?? '') === $plan['slug'] ? ' is-current' : '' ?>">
                    <h3><?= e($plan['name']) ?></h3>
                    <p class="plan-price"><?= e(format_currency((float) $plan['price_monthly'])) ?><span>/<?php _e('subscription.month'); ?></span></p>
                    <ul class="plan-features">
                        <?php if ($plan['max_events']): ?><li><?= (int) $plan['max_events'] ?> <?php _e('subscription.events'); ?></li><?php else: ?><li><?php _e('subscription.unlimited_events'); ?></li><?php endif; ?>
                        <?php if (!empty($features['white_label'])): ?><li><?php _e('subscription.white_label'); ?></li><?php endif; ?>
                        <?php if (!empty($features['ai_matching'])): ?><li><?php _e('subscription.ai_matching'); ?></li><?php endif; ?>
                        <?php if (!empty($features['community'])): ?><li><?php _e('subscription.community'); ?></li><?php endif; ?>
                    </ul>
                    <form method="post"><input type="hidden" name="plan_slug" value="<?= e($plan['slug']) ?>">
                        <button type="submit" class="btn btn-primary btn-sm"><?php _e('subscription.select'); ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
