<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$org = get_organization_for_user((int) current_user()['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('community.title');
$communities = get_org_communities((int) $org['id']);
$series = get_org_recurring_series((int) $org['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_community'])) {
        create_community((int) $org['id'], trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''));
        set_flash('success', __('community.created'));
    }
    if (isset($_POST['create_series'])) {
        create_recurring_series((int) $org['id'], trim($_POST['series_title'] ?? ''), $_POST['frequency'] ?? 'weekly');
        set_flash('success', __('community.series_created'));
    }
    redirect(base_url('organizer/community.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('community.title'); ?></h1></div>

        <div class="card" style="margin-bottom:1.5rem;">
            <h3><?php _e('community.groups'); ?></h3>
            <?php foreach ($communities as $c): ?>
                <p><strong><?= e($c['name']) ?></strong> — <?= (int) $c['member_count'] ?> <?php _e('community.members'); ?></p>
            <?php endforeach; ?>
            <form method="post" class="form-row" style="margin-top:1rem;">
                <input type="hidden" name="create_community" value="1">
                <input class="input" name="name" placeholder="<?= e(__('community.group_name')) ?>" required style="flex:1;">
                <input class="input" name="description" placeholder="<?= e(__('community.description')) ?>">
                <button class="btn btn-primary"><?php _e('community.create'); ?></button>
            </form>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <h3><?php _e('community.recurring'); ?></h3>
            <?php foreach ($series as $s): ?>
                <p><?= e($s['title']) ?> — <?= e($s['frequency']) ?></p>
            <?php endforeach; ?>
            <form method="post" class="form-row" style="margin-top:1rem;">
                <input type="hidden" name="create_series" value="1">
                <input class="input" name="series_title" required style="flex:1;">
                <select name="frequency" class="select"><option value="weekly"><?php _e('community.weekly'); ?></option><option value="biweekly"><?php _e('community.biweekly'); ?></option><option value="monthly"><?php _e('community.monthly'); ?></option></select>
                <button class="btn btn-primary"><?php _e('community.add_series'); ?></button>
            </form>
        </div>

        <div class="card">
            <p><?php _e('community.followers_count', ['count' => org_follower_count((int) $org['id'])]) ?></p>
            <a href="<?= base_url('org/profile.php?org=' . urlencode($org['slug'])) ?>" class="btn btn-outline btn-sm"><?php _e('community.view_profile'); ?></a>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
