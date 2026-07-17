<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('referrals.title');
$bodyClass = 'dashboard-page';
$code = get_or_create_referral_code((int) $org['id'], (int) $user['id']);
$referralUrl = base_url('signup.php?ref=' . urlencode($code) . '&role=participant');
$leaderboard = referral_leaderboard((int) $org['id']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('referrals.title'); ?></h1></div>

        <div class="card" style="margin-bottom:1.5rem;">
            <p><?php _e('referrals.sub'); ?></p>
            <div class="referral-box"><strong><?php _e('referrals.your_code'); ?>:</strong> <code><?= e($code) ?></code></div>
            <div class="form-group"><label><?php _e('referrals.link'); ?></label><input class="input" readonly value="<?= e($referralUrl) ?>"></div>
            <p class="form-help"><?php _e('referrals.reward'); ?></p>
        </div>

        <div class="card">
            <h2><?php _e('referrals.leaderboard'); ?></h2>
            <?php if ($leaderboard): ?>
                <table class="table">
                    <thead><tr><th>#</th><th><?php _e('organizer.name'); ?></th><th><?php _e('referrals.uses'); ?></th><th><?php _e('loyalty.credits'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($leaderboard as $i => $row): ?>
                            <tr><td><?= $i + 1 ?></td><td><?= e($row['full_name']) ?></td><td><?= (int) $row['uses_count'] ?></td><td><?= (int) $row['referral_credits'] ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('referrals.no_data'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
