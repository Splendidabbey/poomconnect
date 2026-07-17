<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.countries');
$countries = platform_countries();

require_once APP_ROOT . '/includes/header.php';
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <h1><?php _e('admin.countries'); ?></h1>
        <table class="table card"><thead><tr><th>Code</th><th><?php _e('admin.country'); ?></th></tr></thead>
        <tbody><?php foreach ($countries as $c): ?><tr><td><?= e($c['code']) ?></td><td><?= e($c['name']) ?></td></tr><?php endforeach; ?></tbody></table>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
