<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.system_logs');
$logs = get_system_logs(100);

require_once APP_ROOT . '/includes/header.php';
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.system_logs'); ?></h1>
        <table class="table card"><thead><tr><th><?php _e('admin.time'); ?></th><th><?php _e('admin.user'); ?></th><th><?php _e('admin.action'); ?></th><th><?php _e('admin.details'); ?></th></tr></thead>
        <tbody><?php foreach ($logs as $l): ?><tr><td><?= e(format_date($l['created_at'])) ?></td><td><?= e($l['full_name']) ?></td><td><?= e($l['action']) ?></td><td><?= e($l['details'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
