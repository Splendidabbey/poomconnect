<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.ai_usage');
$stats = ai_usage_stats(30);
$byOrg = ai_usage_by_org(15);

require_once APP_ROOT . '/includes/header.php';
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.ai_usage'); ?></h1>
        <div class="admin-kpi-card card"><strong><?= (int) $stats['total'] ?></strong> <?php _e('admin.ai_calls_30d'); ?></div>
        <div class="card" style="margin-top:1rem;"><h3><?php _e('admin.by_action'); ?></h3>
            <ul><?php foreach ($stats['by_action'] as $a): ?><li><?= e($a['action']) ?>: <?= (int) $a['cnt'] ?></li><?php endforeach; ?></ul>
        </div>
        <div class="card" style="margin-top:1rem;"><h3><?php _e('admin.by_org'); ?></h3>
            <ul><?php foreach ($byOrg as $o): ?><li><?= e($o['name']) ?>: <?= (int) $o['usage_count'] ?></li><?php endforeach; ?></ul>
        </div>
        <p class="form-help"><?php _e('ai_policy.admin_note'); ?></p>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
